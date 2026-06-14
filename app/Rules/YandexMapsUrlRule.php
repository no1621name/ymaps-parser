<?php

namespace App\Rules;

use App\Exceptions\BusinessIdNotFoundException;
use App\Services\YandexMaps\BusinessId;
use Illuminate\Contracts\Validation\Rule;

class YandexMapsUrlRule implements Rule
{
    private ?BusinessId $businessId = null;

    public function passes($attribute, $value): bool
    {
        try {
            $this->businessId = BusinessId::fromUrl($value);

            return true;
        } catch (BusinessIdNotFoundException) {
            return false;
        }
    }

    public function message(): string
    {
        return 'The :attribute must be a valid Yandex Maps organization URL.';
    }

    public function getBusinessId(): ?BusinessId
    {
        return $this->businessId;
    }
}
