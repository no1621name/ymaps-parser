<?php

namespace App\Console\Commands;

use App\Exceptions\YandexApiException;
use App\Services\YandexMaps\ApiClient;
use App\Services\YandexMaps\BusinessId;
use App\Services\YandexMaps\HtmlParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class YandexMapsCanary extends Command
{
    protected $signature = 'yandex-maps:canary {--business-id=228521220265}';

    protected $description = 'Run a daily canary check against Yandex Maps API to verify signature and response format integrity';

    public function __construct(
        private ApiClient $apiClient,
        private HtmlParser $htmlParser,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $businessIdValue = $this->option('business-id');

        try {
            $businessId = BusinessId::fromString($businessIdValue);

            $html = $this->apiClient->fetchOrgPage($businessId);
            $session = $this->htmlParser->extractSessionData($html);
            $meta = $this->htmlParser->extractMetaData($html);

            $firstPage = $this->apiClient->fetchReviews($businessId, $session, 1);
            $reviewsCount = count($firstPage['data']['reviews'] ?? []);

            $this->info(sprintf(
                '%s: %d отзывов, рейтинг %.1f',
                $meta->name,
                $meta->reviewsCount,
                $meta->avgRating
            ));

            Log::info('Canary check passed', [
                'business_id' => $businessIdValue,
                'name' => $meta->name,
                'reviews_count' => $meta->reviewsCount,
                'avg_rating' => $meta->avgRating,
                'first_page_reviews' => $reviewsCount,
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $message = $e instanceof YandexApiException
                ? 'Canary check failed: '.$e->getMessage()
                : 'Canary check failed: '.get_class($e).': '.$e->getMessage();

            Log::error($message, [
                'business_id' => $businessIdValue,
                'exception' => $e->getTraceAsString(),
            ]);

            $this->error($message);

            return self::FAILURE;
        }
    }
}
