<?php

namespace App\Contracts;

use App\Services\YandexMaps\BusinessId;
use App\Services\YandexMaps\ReviewsParseResult;

interface ReviewParser
{
    public function parse(BusinessId $id): ReviewsParseResult;
}
