<?php

namespace Tests\Unit;

use App\Exceptions\YandexApiException;
use App\Services\YandexMaps\ApiClient;
use App\Services\YandexMaps\BusinessId;
use App\Services\YandexMaps\YandexMapsConfig;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiClientTest extends TestCase
{
    private ApiClient $apiClient;

    private YandexMapsConfig $config;

    private BusinessId $businessId;

    protected function setUp(): void
    {
        parent::setUp();

        config(['yandex_maps.parsing.page_size' => 2]);

        $this->config = YandexMapsConfig::fromConfig();

        $this->businessId = BusinessId::fromString('101601401068');
        $this->apiClient = new ApiClient($this->config);
    }

    public function test_fetch_org_page_success(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/org-page-success.html');

        Http::fake([
            '*' => Http::response($html, 200, ['Content-Type' => 'text/html']),
        ]);

        $result = $this->apiClient->fetchOrgPage($this->businessId);

        $this->assertIsString($result);
        $this->assertStringContainsString('Koferoom', $result);
    }

    public function test_fetch_org_page_failed(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->expectException(YandexApiException::class);
        $this->expectExceptionMessage('Failed to fetch organization page: HTTP 500');

        $this->apiClient->fetchOrgPage($this->businessId);
    }

    public function test_fetch_reviews_success(): void
    {
        $json = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-1.json');

        Http::fake([
            '*' => Http::response($json, 200, ['Content-Type' => 'application/json']),
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $result = $this->apiClient->fetchReviews($this->businessId, $session, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertCount(2, $result['data']['reviews']);
    }

    public function test_fetch_reviews_rate_limit(): void
    {
        $txt = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/rate-limit-response.txt');

        Http::fake([
            '*' => Http::response($txt, 429, ['Content-Type' => 'text/html']),
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $this->expectException(YandexApiException::class);
        $this->expectExceptionCode(429);

        $this->apiClient->fetchReviews($this->businessId, $session, 1);
    }

    public function test_fetch_all_reviews_empty(): void
    {
        $json = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/empty-response.json');

        Http::fake([
            '*' => Http::response($json, 200, ['Content-Type' => 'application/json']),
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $generator = $this->apiClient->fetchAllReviews($this->businessId, $session);

        $this->assertFalse($generator->valid(), 'Generator should be empty for empty first page');
    }

    public function test_fetch_reviews_csrf_expired(): void
    {
        $json = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/csrf-only-response.json');

        Http::fake([
            '*' => Http::response($json, 200, ['Content-Type' => 'application/json']),
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $result = $this->apiClient->fetchReviews($this->businessId, $session, 1);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_all_reviews_two_pages(): void
    {
        $json1 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-1.json');
        $json2 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-2.json');

        Http::fake([
            'yandex.ru/maps/api/business/fetchReviews*' => function (Request $request) use ($json1, $json2) {
                $url = $request->url();
                $params = [];
                parse_str($url, $params);
                $page = (int) ($params['page'] ?? 1);

                if ($page === 1) {
                    return Http::response($json1, 200, ['Content-Type' => 'application/json']);
                }

                if ($page === 2) {
                    return Http::response($json2, 200, ['Content-Type' => 'application/json']);
                }

                return Http::response(json_encode(['data' => ['reviews' => []]]), 200, ['Content-Type' => 'application/json']);
            },
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $allReviews = [];
        foreach ($this->apiClient->fetchAllReviews($this->businessId, $session) as $reviews) {
            $allReviews[] = $reviews;
        }

        $this->assertCount(2, $allReviews);
        $this->assertCount(2, $allReviews[0]);
        $this->assertCount(1, $allReviews[1]);
    }

    public function test_fetch_all_reviews_rate_limit_on_first_page_falls_back_to_sequential(): void
    {
        $rateLimitTxt = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/rate-limit-response.txt');
        $json2 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-2.json');

        Http::fake([
            'yandex.ru/maps/api/business/fetchReviews*' => function (Request $request) use ($rateLimitTxt, $json2) {
                $url = $request->url();
                $params = [];
                parse_str($url, $params);
                $page = (int) ($params['page'] ?? 1);

                if ($page === 1) {
                    return Http::response($rateLimitTxt, 429, ['Content-Type' => 'text/html']);
                }

                return Http::response($json2, 200, ['Content-Type' => 'application/json']);
            },
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $allReviews = [];
        foreach ($this->apiClient->fetchAllReviews($this->businessId, $session) as $reviews) {
            $allReviews[] = $reviews;
        }

        $this->assertCount(1, $allReviews);
        $this->assertCount(1, $allReviews[0]);
    }

    public function test_fetch_all_reviews_rate_limit_on_pooled_requests_falls_back_to_sequential(): void
    {
        $json1 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-1.json');
        $rateLimitTxt = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/rate-limit-response.txt');
        $json2 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-2.json');

        $pageRequestCount = [];

        Http::fake([
            'yandex.ru/maps/api/business/fetchReviews*' => function (Request $request) use ($json1, $rateLimitTxt, $json2, &$pageRequestCount) {
                $url = $request->url();
                $params = [];
                parse_str($url, $params);
                $page = (int) ($params['page'] ?? 1);

                if (! isset($pageRequestCount[$page])) {
                    $pageRequestCount[$page] = 0;
                }
                $pageRequestCount[$page]++;

                if ($page === 1) {
                    return Http::response($json1, 200, ['Content-Type' => 'application/json']);
                }

                if ($pageRequestCount[$page] === 1) {
                    return Http::response($rateLimitTxt, 429, ['Content-Type' => 'text/html']);
                }

                return Http::response($json2, 200, ['Content-Type' => 'application/json']);
            },
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $allReviews = [];
        foreach ($this->apiClient->fetchAllReviews($this->businessId, $session) as $reviews) {
            $allReviews[] = $reviews;
        }

        $this->assertGreaterThan(1, ($pageRequestCount[2] ?? 0), 'Page 2 should have been retried');

        $this->assertCount(2, $allReviews);
        $this->assertCount(2, $allReviews[0]);
        $this->assertCount(1, $allReviews[1]);
    }

    public function test_fetch_all_reviews_single_page(): void
    {
        $json = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-single.json');

        Http::fake([
            'yandex.ru/maps/api/business/fetchReviews*' => Http::response($json, 200, ['Content-Type' => 'application/json']),
        ]);

        $session = [
            'csrfToken' => 'abc123:1781389331',
            'sessionId' => 'req123:1781389331',
            'reqId' => 'stackReq123:1781389331',
        ];

        $allReviews = [];
        foreach ($this->apiClient->fetchAllReviews($this->businessId, $session) as $reviews) {
            $allReviews[] = $reviews;
        }

        $this->assertCount(1, $allReviews);
        $this->assertCount(2, $allReviews[0]);
    }
}
