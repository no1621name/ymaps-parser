<?php

namespace Tests\Unit;

use App\Exceptions\CsrfTokenNotFoundException;
use App\Exceptions\YandexParseException;
use App\Services\YandexMaps\HtmlParser;
use App\Services\YandexMaps\OrganizationMetaData;
use PHPUnit\Framework\TestCase;

class HtmlParserTest extends TestCase
{
    private HtmlParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new HtmlParser;
    }

    public function test_extract_session_data_success(): void
    {
        $html = $this->getSampleHtml();

        $session = $this->parser->extractSessionData($html);

        $this->assertArrayHasKey('csrfToken', $session);
        $this->assertArrayHasKey('sessionId', $session);
        $this->assertArrayHasKey('reqId', $session);
        $this->assertEquals('abc123:1781389331', $session['csrfToken']);
        $this->assertEquals('req123:1781389331', $session['sessionId']);
        $this->assertEquals('stackReq123:1781389331', $session['reqId']);
    }

    public function test_extract_session_data_missing_json_config_throws_parse_exception(): void
    {
        $html = '<html><body>No JSON config here</body></html>';

        $this->expectException(YandexParseException::class);

        $this->parser->extractSessionData($html);
    }

    public function test_extract_session_data_missing_csrf_token_throws_csrf_exception(): void
    {
        $html = '<html><body>
            <script type="application/json">
            {"config": {"requestId": "req123"}, "stack": [{"results": {"requestId": "stackReq123"}}]}
            </script>
        </body></html>';

        $this->expectException(CsrfTokenNotFoundException::class);

        $this->parser->extractSessionData($html);
    }

    public function test_extract_meta_data_success(): void
    {
        $html = $this->getSampleHtml();

        $meta = $this->parser->extractMetaData($html);

        $this->assertInstanceOf(OrganizationMetaData::class, $meta);
        $this->assertEquals('Преображение', $meta->name);
        $this->assertEquals(165, $meta->reviewsCount);
        $this->assertEquals(584, $meta->ratingsCount);
        $this->assertEquals(3.8, $meta->avgRating);
    }

    private function getSampleHtml(): string
    {
        return '<!DOCTYPE html>
<html lang="ru">
<head>
    <script type="application/json">
    {
        "config": {
            "csrfToken": "abc123:1781389331",
            "requestId": "req123:1781389331"
        },
        "stack": [
            {
                "results": {
                    "requestId": "stackReq123:1781389331"
                }
            }
        ]
    }
    </script>
</head>
<body>
<div class="tabs-select-view__title _name_reviews _selected" aria-hidden="false" aria-label="Отзывы, 165" role="tab" aria-selected="true" tabindex="0"><a class="tabs-select-view__label" href="/maps/org/magnit/1132523015/reviews/" tabindex="-1" aria-hidden="true">Отзывы</a><div aria-hidden="true" class="tabs-select-view__counter">165</div></div>
<h1 class="orgpage-header-view__header" itemProp="name">Преображение<span class="business-verified-badge _prioritized" aria-hidden="true"><span tag="span" style="font-size:0;line-height:0" class="inline-image icon" aria-hidden="true"><svg width="12" height="12"></svg></span></span></h1>
<h1 class="card-title-view__title" itemprop="name">Магнит<span class="business-verified-badge _prioritized" aria-hidden="true"><span tag="span" class="inline-image _loaded icon" aria-hidden="true" style="font-size: 0px; line-height: 0;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path fill="#3bb300" fill-rule="evenodd" d="M6 11A5 5 0 1 1 6 1a5 5 0 0 1 0 10" clip-rule="evenodd"></path><path fill="#fff" fill-rule="evenodd" d="m5.807 6.901.648.657a.5.5 0 0 0 .84-.227l.694-2.706a.5.5 0 0 0-.609-.608l-2.684.687a.5.5 0 0 0-.232.836l.641.65-2.263 2.265q.206.25.324.37.12.12.383.337z" clip-rule="evenodd"></path></svg></span></span></h1>
<h2 class="card-section-header__title _wide">165 отзывов</h2>
<div class="business-summary-rating-badge-view__rating"><span class="a11y-hidden">Рейтинг&nbsp;</span><span class="business-summary-rating-badge-view__rating-text">3</span><span class="business-summary-rating-badge-view__rating-text _separator">,</span><span class="business-summary-rating-badge-view__rating-text">8</span></div>
<span class="business-rating-amount-view _summary">584 оценки</span>
</body>
</html>';
    }
}

/*

<h1 class="orgpage-header-view__header" itemprop="name">Тестовая Организация<span class="business-verified-badge _prioritized" aria-hidden="true"><span tag="span" class="inline-image _loaded icon" aria-hidden="true" style="font-size: 0px; line-height: 0;"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path fill="#3bb300" fill-rule="evenodd" d="M6 11A5 5 0 1 1 6 1a5 5 0 0 1 0 10" clip-rule="evenodd"></path><path fill="#fff" fill-rule="evenodd" d="m5.807 6.901.648.657a.5.5 0 0 0 .84-.227l.694-2.706a.5.5 0 0 0-.609-.608l-2.684.687a.5.5 0 0 0-.232.836l.641.65-2.263 2.265q.206.25.324.37.12.12.383.337z" clip-rule="evenodd"></path></svg></span></span></span></h1>
    <div class="business-summary-rating-badge-view__rating-count"><span class="business-rating-amount-view _summary">347 оценок</span></div>
    <div class="business-summary-rating-badge-view__rating"><span class="a11y-hidden">Рейтинг&nbsp;</span><span class="business-summary-rating-badge-view__rating-text">4</span><span class="business-summary-rating-badge-view__rating-text _separator">,</span><span class="business-summary-rating-badge-view__rating-text">2</span></div>
    <div class="tabs-select-view__title _name_reviews _selected" aria-hidden="false" aria-label="Отзывы, 200" role="tab" aria-selected="true" tabindex="0"><a class="tabs-select-view__label" href="/maps/org/preobrazheniye/41332984866/reviews/" tabindex="-1" aria-hidden="true">Отзывы</a><div aria-hidden="true" class="tabs-select-view__counter">200</div></div>

*/
