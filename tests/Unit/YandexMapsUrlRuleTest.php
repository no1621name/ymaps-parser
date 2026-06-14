<?php

namespace Tests\Unit;

use App\Rules\YandexMapsUrlRule;
use PHPUnit\Framework\TestCase;

class YandexMapsUrlRuleTest extends TestCase
{
    private YandexMapsUrlRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new YandexMapsUrlRule;
    }

    public function test_passes_valid_url_format_1(): void
    {
        $result = $this->rule->passes('url', 'https://yandex.ru/maps/org/название/41332984866/');

        $this->assertTrue($result);
        $this->assertNotNull($this->rule->getBusinessId());
        $this->assertEquals('41332984866', $this->rule->getBusinessId()->value);
    }

    public function test_passes_valid_url_format_2(): void
    {
        $result = $this->rule->passes('url', 'https://yandex.ru/maps/5/city/?poi[uri]=ymapsbm1://org?oid=41332984866');

        $this->assertTrue($result);
        $this->assertEquals('41332984866', $this->rule->getBusinessId()->value);
    }

    public function test_passes_valid_url_without_scheme(): void
    {
        $result = $this->rule->passes('url', 'yandex.ru/maps/org/название/41332984866/');

        $this->assertTrue($result);
    }

    public function test_fails_invalid_url(): void
    {
        $result = $this->rule->passes('url', 'https://google.com/');

        $this->assertFalse($result);
    }

    public function test_fails_no_business_id(): void
    {
        $result = $this->rule->passes('url', 'https://yandex.ru/maps/');

        $this->assertFalse($result);
    }

    public function test_message(): void
    {
        $this->assertIsString($this->rule->message());
        $this->assertStringContainsString('Yandex Maps', $this->rule->message());
    }
}
