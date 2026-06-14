<?php

namespace App\Services\YandexMaps;

use App\Enums\OrganizationStatus;
use App\Exceptions\YandexParseException;
use App\Models\Organization;
use App\Models\ParseEvent;
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

            ParseEvent::create([
                'organization_id' => $organization->id,
                'type' => 'info_ready',
            ]);

            foreach ($this->apiClient->fetchAllReviews($businessId, $session) as $reviews) {
                foreach ($reviews as $review) {
                    Review::updateOrCreate(
                        ['review_id' => $review['reviewId']],
                        [
                            'organization_id' => $organization->id,
                            'author_name' => $review['author']['name'] ?? '',
                            'avatar_url' => ($avatarUrl = $review['author']['avatarUrl'] ?? null) ? str_replace('{size}', '', $avatarUrl) : null,
                            'rating' => $review['rating'] ?? 0,
                            'text' => $review['text'] ?? '',
                            'published_at' => $review['updatedTime'] ?? null,
                            'likes' => $review['reactions']['likes'] ?? 0,
                            'dislikes' => $review['reactions']['dislikes'] ?? 0,
                        ]
                    );
                }
            }

            $organization->markAsDone();

            ParseEvent::create([
                'organization_id' => $organization->id,
                'type' => 'reviews_ready',
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to parse organization reviews', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            ParseEvent::create([
                'organization_id' => $organization->id,
                'type' => 'failed',
                'payload' => ['message' => $e->getMessage()],
            ]);

            $organization->markAsFailed($e->getMessage());

            throw new YandexParseException('Failed to parse reviews');
        }
    }
}
