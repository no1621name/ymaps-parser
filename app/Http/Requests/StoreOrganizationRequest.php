<?php

namespace App\Http\Requests;

use App\Rules\YandexMapsUrlRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', new YandexMapsUrlRule],
        ];
    }
}
