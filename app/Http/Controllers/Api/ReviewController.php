<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListReviewsRequest;
use App\Http\Resources\ReviewCollection;
use App\Models\Organization;

class ReviewController extends Controller
{
    public function index(Organization $organization, ListReviewsRequest $request): ReviewCollection
    {
        $reviews = $organization->reviews()
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 50));

        return new ReviewCollection($reviews);
    }
}
