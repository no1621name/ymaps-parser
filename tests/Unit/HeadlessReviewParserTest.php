<?php

namespace Tests\Unit;

use App\Exceptions\YandexParseException;
use App\Services\YandexMaps\BusinessId;
use App\Services\YandexMaps\HeadlessReviewParser;
use Tests\TestCase;

class HeadlessReviewParserTest extends TestCase
{
    public function test_parses_html_correctly(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/YandexMaps/headless-reviews-page.html');

        $parser = $this->getMockBuilder(HeadlessReviewParser::class)
            ->onlyMethods(['fetchHtml'])
            ->getMock();

        $parser->method('fetchHtml')->willReturn($html);

        $businessId = BusinessId::fromString('101601401068');
        $result = $parser->parse($businessId);

        // Check metadata
        $this->assertEquals('Koferoom', $result->meta->name);
        $this->assertEquals(145, $result->meta->reviewsCount);
        $this->assertEquals(145, $result->meta->ratingsCount);
        $this->assertEquals(4.8, $result->meta->avgRating);

        // Check reviews
        $this->assertCount(2, $result->reviews);

        // Review 1
        $review1 = $result->reviews[0];
        $this->assertEquals('Кирилл Дмитриев', $review1['author']['name']);
        $this->assertEquals(5.0, $review1['rating']);
        $this->assertEquals('2023-10-15T12:00:00Z', $review1['updatedTime']);
        $this->assertEquals('Отличный кофе и атмосфера!', $review1['text']);
        $this->assertEquals(12, $review1['reactions']['likes']);
        $this->assertEquals(1, $review1['reactions']['dislikes']);
        $this->assertNotEmpty($review1['reviewId']);

        // Review 2
        $review2 = $result->reviews[1];
        $this->assertEquals('Наталья', $review2['author']['name']);
        $this->assertEquals(4.0, $review2['rating']);
        $this->assertEquals('2023-10-16T14:30:00Z', $review2['updatedTime']);
        $this->assertEquals('Хорошо, но бывает людно.', $review2['text']);
        $this->assertEquals(0, $review2['reactions']['likes']);
        $this->assertEquals(0, $review2['reactions']['dislikes']);
        $this->assertNotEmpty($review2['reviewId']);
    }

    public function test_throws_exception_on_empty_html(): void
    {
        $parser = $this->getMockBuilder(HeadlessReviewParser::class)
            ->onlyMethods(['fetchHtml'])
            ->getMock();

        $parser->method('fetchHtml')->willReturn('');

        $this->expectException(YandexParseException::class);
        $this->expectExceptionMessage('Headless browser returned empty HTML');

        $businessId = BusinessId::fromString('101601401068');
        $parser->parse($businessId);
    }
}
