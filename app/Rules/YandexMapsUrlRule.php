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
        if (! BusinessId::isUrlSupported($value)) {
            return false;
        }

        try {
            $this->businessId = BusinessId::fromUrl($value);
        } catch (BusinessIdNotFoundException) {
            // Short URLs are valid but don't contain the business ID directly
        }

        return true;
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
