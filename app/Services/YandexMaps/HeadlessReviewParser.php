<?php

namespace App\Services\YandexMaps;

use App\Contracts\ReviewParser;
use App\Exceptions\YandexParseException;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class HeadlessReviewParser implements ReviewParser
{
    public function parse(BusinessId $id): ReviewsParseResult
    {
        Log::info('Using HeadlessReviewParser for business ID: '.$id->toString());

        $url = 'https://yandex.ru/maps/org/'.$id->toString().'/reviews/';

        $html = $this->fetchHtml($url);

        if (empty($html)) {
            throw new YandexParseException('Headless browser returned empty HTML');
        }

        $crawler = new Crawler($html);

        // Extract metadata
        $name = $crawler->filter('h1[itemprop="name"]')->count() > 0
            ? trim($crawler->filter('h1[itemprop="name"]')->text())
            : '';

        $reviewsCount = 0;
        if (preg_match('/aria-label="Отзывы,\s*(\d+)"/u', $html, $matches)) {
            $reviewsCount = (int) $matches[1];
        } elseif (preg_match('/tabs-select-view__counter[^>]*>(\d+)/', $html, $matches)) {
            $reviewsCount = (int) $matches[1];
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

        $meta = new OrganizationMetaData($name, $reviewsCount, $ratingsCount, $avgRating);

        // Parse reviews
        $reviews = [];
        $crawler->filter('.business-review-view')->each(function (Crawler $node) use (&$reviews) {
            $authorName = $node->filter('.business-review-view__link > [itemprop="name"]')->count() > 0
                ? trim($node->filter('.business-review-view__link > [itemprop="name"]')->text())
                : '';

            $rating = 0;
            $ratingNode = $node->filter('.business-review-view__header > .business-review-view__rating > span[itemprop="reviewRating"] > meta[itemprop="bestRating"]');
            if ($ratingNode->count() > 0) {
                $rating = (float) $ratingNode->attr('content');
            }

            $publishedAt = null;
            $dateNode = $node->filter('.business-review-view__date > meta[itemprop="datePublished"]');
            if ($dateNode->count() > 0) {
                $publishedAt = $dateNode->attr('content');
            }

            $text = $node->filter('.spoiler-view__text-container')->count() > 0
                ? trim($node->filter('.spoiler-view__text-container')->text())
                : '';

            $likes = 0;
            $likesNode = $node->filter('.business-reactions-view__container[aria-label="Лайк"] .business-reactions-view__counter');
            if ($likesNode->count() > 0) {
                $likes = (int) $likesNode->text();
            }

            $dislikes = 0;
            $dislikesNode = $node->filter('.business-reactions-view__container[aria-label="Дизлайк"] .business-reactions-view__counter');
            if ($dislikesNode->count() > 0) {
                $dislikes = (int) $dislikesNode->text();
            }

            $reviewId = md5($authorName.$publishedAt.$text); // fallback ID since real ID is in data attributes

            $reviews[] = [
                'reviewId' => $reviewId,
                'author' => ['name' => $authorName],
                'rating' => $rating,
                'text' => $text,
                'updatedTime' => $publishedAt,
                'reactions' => [
                    'likes' => $likes,
                    'dislikes' => $dislikes,
                ],
            ];
        });

        return new ReviewsParseResult($meta, $reviews);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function fetchHtml(string $url): string
    {
        $browsershot = Browsershot::url($url)
            ->noSandbox()
            ->setNodeBinary(env('NODE_BINARY', 'node'))
            ->setNpmBinary(env('NPM_BINARY', 'npm'))
            ->windowSize(1920, 1080)
            ->waitUntilNetworkIdle();

        if (file_exists('/usr/bin/chromium-browser')) {
            $browsershot->setChromePath('/usr/bin/chromium-browser');
        }

        return $browsershot->evaluate(<<<'JS'
            return new Promise((resolve) => {
                const container = document.querySelector('.scroll__container');
                if (!container) {
                    resolve(document.documentElement.outerHTML);
                    return;
                }

                let lastHeight = container.scrollHeight;
                let scrollAttempts = 0;

                const scrollDown = setInterval(() => {
                    container.scrollTop = container.scrollHeight;
                    scrollAttempts++;

                    if (scrollAttempts > 50) {
                        clearInterval(scrollDown);
                        resolve(document.documentElement.outerHTML);
                        return;
                    }

                    setTimeout(() => {
                        const newHeight = container.scrollHeight;
                        if (newHeight === lastHeight) {
                            clearInterval(scrollDown);
                            resolve(document.documentElement.outerHTML);
                        } else {
                            lastHeight = newHeight;
                        }
                    }, 1000); // Wait 1000ms after each scroll
                }, 2000); // Scroll every 2000ms
            });
JS
        );
    }
}
