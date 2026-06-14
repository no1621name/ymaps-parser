<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrganizationCollection extends ResourceCollection
{
    public $collects = OrganizationResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }

    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'current_page' => $default['meta']['current_page'],
                'total' => $default['meta']['total'],
                'per_page' => $default['meta']['per_page'],
            ],
        ];
    }
}
