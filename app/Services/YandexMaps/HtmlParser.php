<?php

namespace App\Services\YandexMaps;

use App\Exceptions\CsrfTokenNotFoundException;
use App\Exceptions\YandexParseException;

class HtmlParser
{
    public function extractSessionData(string $html): array
    {
        if (! preg_match('/<script[^>]*type="application\/json"[^>]*>([^<]+)<\/script>/s', $html, $matches)) {
            throw new YandexParseException('Could not find JSON config in page HTML');
        }

        $jsonData = json_decode($matches[1], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new YandexParseException('Could not parse JSON config: '.json_last_error_msg());
        }

        if (! isset($jsonData['config']['csrfToken'])) {
            throw new CsrfTokenNotFoundException;
        }

        return [
            'csrfToken' => $jsonData['config']['csrfToken'],
            'sessionId' => $jsonData['config']['requestId'] ?? '',
            'reqId' => $jsonData['stack'][0]['results']['requestId'] ?? '',
        ];
    }

    public function extractMetaData(string $html): OrganizationMetaData
    {
        $name = '';
        if (preg_match('/item[pP]rop="name">([^<]+)/u', $html, $matches)) {
            $name = trim($matches[1]);
        }

        $ratingsCount = 0;
        if (preg_match('/business-rating-amount-view[^>]*>(\d[\d\s]*)\s*оцен/u', $html, $matches)) {
            $ratingsCount = (int) str_replace(' ', '', trim($matches[1]));
        }

        $avgRating = 0.0;
        if (preg_match_all('/business-summary-rating-badge-view__rating-text[^>]*>([^<]+)/u', $html, $matches)) {
            $digits = array_filter($matches[1], fn ($v) => $v !== ',' && $v !== '.' && trim($v) !== '' && ! str_contains($v, 'Рейтинг'));
            $digits = array_values($digits);
            if (count($digits) >= 2) {
                $avgRating = (float) ($digits[0].'.'.$digits[1]);
            } elseif (count($digits) === 1) {
                $avgRating = (float) $digits[0];
            }
        }

        $reviewsCount = 0;
        if (preg_match('/aria-label="Отзывы,\s*(\d+)"/u', $html, $matches)) {
            $reviewsCount = (int) $matches[1];
        } elseif (preg_match('/tabs-select-view__counter[^>]*>(\d+)/', $html, $matches)) {
            $reviewsCount = (int) $matches[1];
        }

        return new OrganizationMetaData(
            name: $name,
            reviewsCount: $reviewsCount,
            ratingsCount: $ratingsCount,
            avgRating: $avgRating,
        );
    }
}
