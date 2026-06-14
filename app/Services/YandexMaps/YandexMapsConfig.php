<?php

namespace App\Services\YandexMaps;

final readonly class YandexMapsConfig
{
    private function __construct(
        public string $baseUrl,
        public string $apiEndpoint,
        public string $userAgent,
        public array $headers,
        public array $htmlHeaders,
        public int $pageSize,
        public int $maxPages,
        public int $minDelayMs,
        public int $maxDelayMs,
        public int $rateLimitMinutes,
        public int $retryTries,
        public array $retryBackoff,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            baseUrl: config('yandex_maps.base_url'),
            apiEndpoint: config('yandex_maps.api_endpoint'),
            userAgent: config('yandex_maps.user_agent'),
            headers: config('yandex_maps.headers'),
            htmlHeaders: config('yandex_maps.html_headers'),
            pageSize: config('yandex_maps.parsing.page_size'),
            maxPages: config('yandex_maps.parsing.max_pages'),
            minDelayMs: config('yandex_maps.parsing.min_delay_ms'),
            maxDelayMs: config('yandex_maps.parsing.max_delay_ms'),
            rateLimitMinutes: config('yandex_maps.parsing.rate_limit_minutes'),
            retryTries: config('yandex_maps.retry.tries'),
            retryBackoff: config('yandex_maps.retry.backoff'),
        );
    }
}
