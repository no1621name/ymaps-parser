<?php

namespace Tests\Unit;

use App\Exceptions\BusinessIdNotFoundException;
use App\Services\YandexMaps\BusinessId;
use PHPUnit\Framework\TestCase;

class BusinessIdTest extends TestCase
{
    public function test_from_string_valid(): void
    {
        $id = BusinessId::fromString('41332984866');

        $this->assertEquals('41332984866', $id->value);
    }

    public function test_from_string_valid_10_digits(): void
    {
        $id = BusinessId::fromString('1234567890');

        $this->assertEquals('1234567890', $id->value);
    }

    public function test_from_string_valid_12_digits(): void
    {
        $id = BusinessId::fromString('123456789012');

        $this->assertEquals('123456789012', $id->value);
    }

    public function test_from_string_invalid_too_short(): void
    {
        $this->expectException(BusinessIdNotFoundException::class);

        BusinessId::fromString('123456789');
    }

    public function test_from_string_invalid_too_long(): void
    {
        $this->expectException(BusinessIdNotFoundException::class);

        BusinessId::fromString('1234567890123');
    }

    public function test_from_string_invalid_format(): void
    {
        $this->expectException(BusinessIdNotFoundException::class);

        BusinessId::fromString('abc12345678');
    }

    public function test_from_url_format_1(): void
    {
        $id = BusinessId::fromUrl('https://yandex.ru/maps/org/название/41332984866/');

        $this->assertEquals('41332984866', $id->value);
    }

    public function test_from_url_format_2(): void
    {
        $id = BusinessId::fromUrl('https://yandex.ru/maps/5/city/?poi[uri]=ymapsbm1://org?oid=41332984866');

        $this->assertEquals('41332984866', $id->value);
    }

    public function test_from_url_format_3(): void
    {
        $id = BusinessId::fromUrl('https://yandex.ru/maps/5/city/41332984866');

        $this->assertEquals('41332984866', $id->value);
    }

    public function test_from_url_invalid(): void
    {
        $this->expectException(BusinessIdNotFoundException::class);

        BusinessId::fromUrl('https://yandex.ru/maps/');
    }

    public function test_to_string(): void
    {
        $id = BusinessId::fromString('41332984866');

        $this->assertEquals('41332984866', $id->toString());
    }
}
