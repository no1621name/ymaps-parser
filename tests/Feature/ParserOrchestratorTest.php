<?php

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Exceptions\YandexParseException;
use App\Models\Organization;
use App\Services\YandexMaps\ApiClient;
use App\Services\YandexMaps\HtmlParser;
use App\Services\YandexMaps\ParserOrchestrator;
use App\Services\YandexMaps\YandexMapsConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ParserOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private ParserOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        $config = YandexMapsConfig::fromConfig();
        $htmlParser = new HtmlParser;
        $apiClient = new ApiClient($config);

        $this->orchestrator = new ParserOrchestrator($htmlParser, $apiClient);
    }

    public function test_full_parse_pipeline(): void
    {
        $orgHtml = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/org-page-success.html');
        $reviewsJson1 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-1.json');
        $reviewsJson2 = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/reviews-page-2.json');

        Http::fake([
            'yandex.ru/maps/org/*/reviews/*' => Http::response($orgHtml, 200, ['Content-Type' => 'text/html']),
            'yandex.ru/maps/api/business/fetchReviews*' => function ($request) use ($reviewsJson1, $reviewsJson2) {
                $url = $request->url();
                $params = [];
                parse_str($url, $params);
                $page = (int) ($params['page'] ?? 1);

                if ($page === 1) {
                    return Http::response($reviewsJson1, 200, ['Content-Type' => 'application/json']);
                }

                if ($page === 2) {
                    return Http::response($reviewsJson2, 200, ['Content-Type' => 'application/json']);
                }

                return Http::response(json_encode(['data' => ['reviews' => []]]), 200, ['Content-Type' => 'application/json']);
            },
        ]);

        $organization = Organization::factory()->create([
            'business_id' => '101601401068',
            'status' => OrganizationStatus::Pending,
        ]);

        $this->orchestrator->parse($organization);

        $organization->refresh();
        $this->assertEquals('Koferoom', $organization->name);
        $this->assertEquals(145, $organization->reviews_count);
        $this->assertEquals(OrganizationStatus::Done->value, $organization->status->value);

        $this->assertDatabaseCount('reviews', 3);
        $this->assertDatabaseHas('reviews', ['review_id' => '5Qeq3d4e84dkvPtEtgr09nOA15crVLdIg', 'author_name' => 'Кирилл Дмитриев']);
        $this->assertDatabaseHas('reviews', ['review_id' => 'mBK2JecOpO9sKC9tBCfIsJ6DjLVP7YI', 'author_name' => 'Наталья']);
        $this->assertDatabaseHas('reviews', ['review_id' => 'mG61dMx19C99ePbV79h3iB5gwcA7lWT5', 'author_name' => 'Павел Добрынин']);

        $this->assertDatabaseHas('parse_events', [
            'organization_id' => $organization->id,
            'type' => 'info_ready',
        ]);
        $this->assertDatabaseHas('parse_events', [
            'organization_id' => $organization->id,
            'type' => 'reviews_ready',
        ]);
    }

    public function test_parse_fails_on_api_error(): void
    {
        Http::fake([
            'yandex.ru/maps/org/*/reviews/*' => Http::response('', 500),
        ]);

        $organization = Organization::factory()->create([
            'business_id' => '101601401068',
            'status' => OrganizationStatus::Pending,
        ]);

        $this->expectException(YandexParseException::class);

        try {
            $this->orchestrator->parse($organization);
        } finally {
            $organization->refresh();
            $this->assertEquals(OrganizationStatus::Failed, $organization->status);
            $this->assertNotNull($organization->error_message);

            $this->assertDatabaseHas('parse_events', [
                'organization_id' => $organization->id,
                'type' => 'failed',
            ]);
        }
    }

    public function test_parse_fails_on_csrf_expired(): void
    {
        $csrfOnlyJson = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/csrf-only-response.json');

        Http::fake([
            'yandex.ru/maps/org/*/reviews/*' => Http::response($csrfOnlyJson, 200, ['Content-Type' => 'application/json']),
        ]);

        $organization = Organization::factory()->create([
            'business_id' => '101601401068',
            'status' => OrganizationStatus::Pending,
        ]);

        $this->expectException(YandexParseException::class);

        try {
            $this->orchestrator->parse($organization);
        } finally {
            $organization->refresh();
            $this->assertEquals(OrganizationStatus::Failed, $organization->status);
            $this->assertNotNull($organization->error_message);

            $this->assertDatabaseHas('parse_events', [
                'organization_id' => $organization->id,
                'type' => 'failed',
            ]);
        }
    }
}
