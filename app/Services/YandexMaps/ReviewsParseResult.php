<?php

namespace App\Services\YandexMaps;

final readonly class ReviewsParseResult
{
    public function __construct(
        public OrganizationMetaData $meta,
        /** @var iterable<array> */
        public iterable $reviews,
    ) {}
}
