<?php

namespace App\Services\YandexMaps;

use App\Contracts\ReviewParser;
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
        private ReviewParser $reviewParser,
        private HeadlessReviewParser $headlessParser,
    ) {}

    public function parse(Organization $organization): void
    {
        try {
            $businessId = BusinessId::fromString($organization->business_id);

            try {
                $result = $this->reviewParser->parse($businessId);

                if ($result->meta->reviewsCount > 0) {
                    $hasReviews = false;
                    foreach ($result->reviews as $r) {
                        $hasReviews = true;
                        break;
                    }
                    if (! $hasReviews) {
                        throw new YandexParseException('API returned empty reviews but count > 0');
                    }
                }
            } catch (Throwable $e) {
                Log::warning('Falling back to headless parser due to: '.$e->getMessage(), [
                    'organization_id' => $organization->id,
                ]);

                $result = $this->headlessParser->parse($businessId);
            }

            $organization->update([
                'name' => $result->meta->name,
                'reviews_count' => $result->meta->reviewsCount,
                'ratings_count' => $result->meta->ratingsCount,
                'avg_rating' => $result->meta->avgRating,
                'status' => OrganizationStatus::Parsing,
            ]);

            ParseEvent::create([
                'organization_id' => $organization->id,
                'type' => 'info_ready',
            ]);

            foreach ($result->reviews as $review) {
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
