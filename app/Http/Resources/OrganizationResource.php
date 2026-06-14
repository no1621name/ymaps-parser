<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'avg_rating' => $this->avg_rating,
            'reviews_count' => $this->reviews_count,
            'ratings_count' => $this->ratings_count,
            'status' => $this->status->value,
            'error_message' => $this->error_message,
            'parsed_at' => $this->parsed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
