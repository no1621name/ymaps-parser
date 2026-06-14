<?php

namespace Tests\Unit;

use App\Services\YandexMaps\YandexMapsConfig;
use Tests\TestCase;

class YandexMapsConfigTest extends TestCase
{
    public function test_from_config_returns_config_values(): void
    {
        $this->app->instance('config', new class
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return match ($key) {
                    'yandex_maps.base_url' => 'https://yandex.ru/maps',
                    'yandex_maps.api_endpoint' => 'https://yandex.ru/maps/api/business/fetchReviews',
                    'yandex_maps.user_agent' => 'Mozilla/5.0 Test',
                    'yandex_maps.headers' => ['test' => 'header'],
                    'yandex_maps.html_headers' => ['test' => 'html-header'],
                    'yandex_maps.parsing.page_size' => 50,
                    'yandex_maps.parsing.max_pages' => 12,
                    'yandex_maps.parsing.min_delay_ms' => 500,
                    'yandex_maps.parsing.max_delay_ms' => 1500,
                    'yandex_maps.parsing.rate_limit_minutes' => 60,
                    'yandex_maps.parsing.concurrency' => 5,
                    'yandex_maps.retry.tries' => 3,
                    'yandex_maps.retry.backoff' => [1, 5, 10],
                    default => $default,
                };
            }
        });

        $config = YandexMapsConfig::fromConfig();

        $this->assertEquals('https://yandex.ru/maps', $config->baseUrl);
        $this->assertEquals('https://yandex.ru/maps/api/business/fetchReviews', $config->apiEndpoint);
        $this->assertEquals('Mozilla/5.0 Test', $config->userAgent);
        $this->assertEquals(['test' => 'header'], $config->headers);
        $this->assertEquals(['test' => 'html-header'], $config->htmlHeaders);
        $this->assertEquals(50, $config->pageSize);
        $this->assertEquals(12, $config->maxPages);
        $this->assertEquals(500, $config->minDelayMs);
        $this->assertEquals(1500, $config->maxDelayMs);
        $this->assertEquals(60, $config->rateLimitMinutes);
        $this->assertEquals(5, $config->concurrency);
        $this->assertEquals(3, $config->retryTries);
        $this->assertEquals([1, 5, 10], $config->retryBackoff);
    }

    public function test_config_properties_are_readonly(): void
    {
        $reflection = new \ReflectionClass(YandexMapsConfig::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }
}
