<?php

namespace App\Services\YandexMaps;

use App\Exceptions\YandexApiException;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $url = $this->config->apiEndpoint.'?'.$queryString.'&s='.$s;

        $headers = array_merge(
            $this->config->headers,
            [
                'user-agent' => $this->config->userAgent,
                'referer' => $this->config->baseUrl.'/maps/org/'.$id->toString().'/reviews/',
                'x-retpath-y' => $this->config->baseUrl.'/maps/org/'.$id->toString().'/reviews/',
            ]
        );

        $response = Http::withOptions(['cookies' => $this->cookieJar()])
            ->withHeaders($headers)
            ->get($url);

        Log::info(''.$id->toString().''.$response->getBody());

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

    public function fetchAllReviews(BusinessId $id, array $session): \Generator
    {
        $page = 1;

        while ($page <= $this->config->maxPages) {
            $response = $this->fetchReviews($id, $session, $page);

            if (empty($response['data']['reviews'])) {
                break;
            }

            yield $response['data']['reviews'];

            if (count($response['data']['reviews']) < $this->config->pageSize) {
                break;
            }

            $page++;

            usleep(random_int($this->config->minDelayMs, $this->config->maxDelayMs) * 1000);
        }
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
