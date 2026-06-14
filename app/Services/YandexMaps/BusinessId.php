<?php

namespace App\Services\YandexMaps;

use App\Exceptions\BusinessIdNotFoundException;

final class BusinessId
{
    private function __construct(
        public readonly string $value,
    ) {}

    public static function fromString(string $id): self
    {
        if (! preg_match('/^\d{10,12}$/', $id)) {
            throw new BusinessIdNotFoundException;
        }

        return new self($id);
    }

    public static function fromUrl(string $url): self
    {
        $url = urldecode($url);

        if (preg_match('#/maps/org/[^/]+/(\d{10,12})/#', $url, $matches)) {
            return new self($matches[1]);
        }

        if (preg_match('/oid=(\d{10,12})/', $url, $matches)) {
            return new self($matches[1]);
        }

        if (preg_match('#/maps/[^/]+/[^/]+/(\d{10,12})#', $url, $matches)) {
            return new self($matches[1]);
        }

        throw new BusinessIdNotFoundException;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
