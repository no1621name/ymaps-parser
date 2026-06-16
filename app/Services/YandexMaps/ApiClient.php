<?php

namespace App\Services\YandexMaps;

use App\Exceptions\YandexApiException;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    private ?CookieJar $cookieJar = null;

    public function __construct(
        private YandexMapsConfig $config,
    ) {}

    private function cookieJar(): CookieJar
    {
        return $this->cookieJar ??= new CookieJar;
    }

    public function resolveUrl(string $url): string
    {
        $response = Http::withOptions([
            'allow_redirects' => false,
            'cookies' => $this->cookieJar(),
        ])
            ->withHeaders(['user-agent' => $this->config->userAgent])
            ->get($url);

        $location = $response->header('Location');

        if ($location === null) {
            throw new YandexApiException('Short URL did not return a redirect Location header');
        }

        if (! str_starts_with($location, 'http')) {
            $parsed = parse_url($url);
            $location = ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? 'yandex.ru').$location;
        }

        $resolved = urldecode($location);

        if (! BusinessId::isUrlSupported($resolved) || BusinessId::isShortUrl($resolved)) {
            throw new YandexApiException('Resolved URL is not a valid organization page');
        }

        return $resolved;
    }

    public function fetchOrgPage(BusinessId $id): string
    {
        $htmlHeaders = array_merge(
            $this->config->htmlHeaders,
            ['user-agent' => $this->config->userAgent]
        );

        $response = Http::withOptions(['cookies' => $this->cookieJar()])
            ->withHeaders($htmlHeaders)
            ->withHeaders([
                'referer' => $this->config->baseUrl.'/maps/',
                'sec-fetch-site' => 'same-origin',
            ])
            ->get($this->config->baseUrl.'/maps/org/'.$id->toString().'/reviews/');

        if ($response->failed()) {
            throw new YandexApiException('Failed to fetch organization page: HTTP '.$response->status());
        }

        return $response->body();
    }

    public function fetchReviews(BusinessId $id, array $session, int $page): array
    {
        $response = $this->sendReviewRequest($id, $session, $page, $this->buildReviewHeaders($id));

        return $this->parseReviewResponse($response);
    }

    public function fetchAllReviews(BusinessId $id, array $session): \Generator
    {
        try {
            $firstResponse = $this->fetchReviews($id, $session, 1);
        } catch (YandexApiException $e) {
            if ($e->getCode() === 429) {
                $sequential = true;
                $firstResponse = null;
            } else {
                throw $e;
            }
        }

        if ($firstResponse !== null) {
            if (empty($firstResponse['data']['reviews'])) {
                return;
            }

            yield $firstResponse['data']['reviews'];

            if (count($firstResponse['data']['reviews']) < $this->config->pageSize) {
                return;
            }
        }

        $sequential ??= false;
        $pages = range(2, $this->config->maxPages);
        $headers = $this->buildReviewHeaders($id);

        if ($sequential) {
            yield from $this->fetchPagesSequential($id, $session, $pages, $headers);

            return;
        }

        try {
            $responses = Http::pool(function (Pool $pool) use ($id, $session, $pages, $headers) {
                foreach ($pages as $page) {
                    $pool->as('page_'.$page)
                        ->withOptions(['cookies' => $this->cookieJar()])
                        ->withHeaders($headers)
                        ->get($this->buildReviewUrl($id, $session, $page));
                }
            }, concurrency: $this->config->concurrency);

            foreach ($pages as $page) {
                $data = $this->parseReviewResponse($responses['page_'.$page]);

                if (empty($data['data']['reviews'])) {
                    return;
                }

                yield $data['data']['reviews'];

                if (count($data['data']['reviews']) < $this->config->pageSize) {
                    return;
                }
            }
        } catch (YandexApiException $e) {
            if ($e->getCode() !== 429) {
                throw $e;
            }

            yield from $this->fetchPagesSequential($id, $session, $pages, $headers);
        }
    }

    private function sendReviewRequest(BusinessId $id, array $session, int $page, array $headers): Response
    {
        return Http::withOptions(['cookies' => $this->cookieJar()])
            ->withHeaders($headers)
            ->get($this->buildReviewUrl($id, $session, $page));
    }

    private function fetchPagesSequential(BusinessId $id, array $session, array $pages, array $headers): \Generator
    {
        foreach ($pages as $page) {
            $delay = rand($this->config->minDelayMs, $this->config->maxDelayMs) * 1000;
            usleep($delay);

            $response = $this->sendReviewRequest($id, $session, $page, $headers);

            try {
                $data = $this->parseReviewResponse($response);
            } catch (YandexApiException $e) {
                if ($e->getCode() === 429) {
                    usleep($this->config->maxDelayMs * 1000);
                    $response = $this->sendReviewRequest($id, $session, $page, $headers);
                    $data = $this->parseReviewResponse($response);
                } else {
                    throw $e;
                }
            }

            if (empty($data['data']['reviews'])) {
                return;
            }

            yield $data['data']['reviews'];

            if (count($data['data']['reviews']) < $this->config->pageSize) {
                return;
            }
        }
    }

    private function buildReviewUrl(BusinessId $id, array $session, int $page): string
    {
        $params = [
            'ajax' => '1',
            'businessId' => $id->toString(),
            'csrfToken' => $session['csrfToken'],
            'locale' => 'ru_RU',
            'page' => (string) $page,
            'pageSize' => (string) $this->config->pageSize,
            'ranking' => 'by_relevance_org',
            'reqId' => $session['reqId'],
            'sessionId' => $session['sessionId'],
        ];

        ksort($params);

        $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $s = $this->djb2Hash($queryString);

        return $this->config->apiEndpoint.'?'.$queryString.'&s='.$s;
    }

    private function buildReviewHeaders(BusinessId $id): array
    {
        return array_merge(
            $this->config->headers,
            [
                'user-agent' => $this->config->userAgent,
                'referer' => $this->config->baseUrl.'/maps/org/'.$id->toString().'/reviews/',
                'x-retpath-y' => $this->config->baseUrl.'/maps/org/'.$id->toString().'/reviews/',
            ]
        );
    }

    private function parseReviewResponse(Response $response): array
    {
        if ($response->status() === 429) {
            throw new YandexApiException('Yandex API rate limit: 429', code: 429);
        }

        if ($response->status() !== 200) {
            throw new YandexApiException('Yandex API HTTP error: '.$response->status());
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new YandexApiException('Yandex API returned non-JSON response');
        }

        if (isset($data['csrfToken']) && ! isset($data['data'])) {
            $this->cookieJar = null;

            return [];
        }

        return $data;
    }

    private function djb2Hash(string $str): string
    {
        $hash = 5381;

        for ($i = 0; $i < strlen($str); $i++) {
            $hash = (($hash << 5) + $hash) ^ ord($str[$i]);
            $hash = $hash & 0xFFFFFFFF;
        }

        return sprintf('%u', $hash);
    }
}
