<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            'id' => $this->id,
            'review_id' => $this->review_id,
            'author_name' => $this->author_name,
            'avatar_url' => $this->avatar_url,
            'rating' => $this->rating,
            'text' => $this->text,
            'published_at' => $this->published_at->toISOString(),
        ];
    }
}
