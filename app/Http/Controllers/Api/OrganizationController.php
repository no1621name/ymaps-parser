<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexOrganizationRequest;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Resources\OrganizationCollection;
use App\Http\Resources\OrganizationResource;
use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use App\Services\YandexMaps\ApiClient;
use App\Services\YandexMaps\BusinessId;
use App\Services\YandexMaps\HtmlParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(IndexOrganizationRequest $request): OrganizationCollection
    {
        $organizations = Organization::with('latestReview')
            ->orderByDesc('updated_at')
            ->paginate($request->validated('per_page', 20));

        return new OrganizationCollection($organizations);
    }

    public function show(Organization $organization): OrganizationResource
    {
        $organization->load('reviews');

        return new OrganizationResource($organization);
    }

    public function store(StoreOrganizationRequest $request, ApiClient $apiClient, HtmlParser $htmlParser): JsonResponse
    {
        $businessId = BusinessId::fromUrl($request->validated('url'));

        $organization = Organization::firstOrCreate(
            ['business_id' => $businessId->value],
            [
                'name' => 'Pending...',
                'status' => 'pending',
            ],
        );

        ParseOrganizationJob::dispatch($organization->id);

        return (new OrganizationResource($organization->load('reviews')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return response()->json(null, 204);
    }

    public function refresh(Organization $organization)
    {
        if (! $organization->isParsingAllowed()) {
            return response()->json([
                'message' => 'Organization is being parsed or was parsed recently',
            ], 429);
        }

        ParseOrganizationJob::dispatch($organization->id);

        return new OrganizationResource($organization);
    }
}
