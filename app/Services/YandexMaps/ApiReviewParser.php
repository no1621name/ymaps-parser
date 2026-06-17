<?php

namespace App\Services\YandexMaps;

use App\Contracts\ReviewParser;

class ApiReviewParser implements ReviewParser
{
    public function __construct(
        private readonly HtmlParser $htmlParser,
        private readonly ApiClient $apiClient,
    ) {}

    public function parse(BusinessId $id): ReviewsParseResult
    {
        $html = $this->apiClient->fetchOrgPage($id);
        $session = $this->htmlParser->extractSessionData($html);
        $meta = $this->htmlParser->extractMetaData($html);

        $reviews = function () use ($id, $session) {
            foreach ($this->apiClient->fetchAllReviews($id, $session) as $batch) {
                foreach ($batch as $review) {
                    yield $review;
                }
            }
        };

        return new ReviewsParseResult($meta, $reviews());
    }
}
