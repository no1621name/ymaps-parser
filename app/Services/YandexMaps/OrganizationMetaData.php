<?php

namespace App\Services\YandexMaps;

final readonly class OrganizationMetaData
{
    public function __construct(
        public string $name,
        public int $reviewsCount,
        public int $ratingsCount,
        public float $avgRating,
    ) {}
}
