<?php

namespace App\Services\YandexMaps;

use App\Enums\OrganizationStatus;
use App\Exceptions\YandexParseException;
use App\Models\Organization;
use App\Models\Review;
use Illuminate\Support\Facades\Log;
use Throwable;

class ParserOrchestrator
{
    public function __construct(
        private HtmlParser $htmlParser,
        private ApiClient $apiClient,
    ) {}

    public function parse(Organization $organization): void
    {
        try {
            $businessId = BusinessId::fromString($organization->business_id);

            $html = $this->apiClient->fetchOrgPage($businessId);
            $session = $this->htmlParser->extractSessionData($html);
            $meta = $this->htmlParser->extractMetaData($html);

            $organization->update([
                'name' => $meta->name,
                'reviews_count' => $meta->reviewsCount,
                'ratings_count' => $meta->ratingsCount,
                'avg_rating' => $meta->avgRating,
                'status' => OrganizationStatus::Parsing,
            ]);

            foreach ($this->apiClient->fetchAllReviews($businessId, $session) as $reviews) {
                foreach ($reviews as $review) {
                    Review::updateOrCreate(
                        ['review_id' => $review['reviewId']],
                        [
                            'organization_id' => $organization->id,
                            'author_name' => $review['author']['name'] ?? '',
                            'avatar_url' => $review['author']['avatarUrl'] ? str_replace('{size}', '', $review['author']['avatarUrl']) : null,
                            'rating' => $review['rating'] ?? 0,
                            'text' => $review['text'] ?? '',
                            'published_at' => $review['updatedTime'] ?? null,
                        ]
                    );
                }
            }

            $organization->markAsDone();
        } catch (Throwable $e) {
            Log::error('Failed to parse organization reviews', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $organization->markAsFailed($e->getMessage());

            throw new YandexParseException('Failed to parse reviews');
        }
    }
}
