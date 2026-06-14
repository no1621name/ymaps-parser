<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_organization(): void
    {
        $org = Organization::factory()->create();
        $review = Review::factory()->create(['organization_id' => $org->id]);

        $this->assertEquals($org->id, $review->organization->id);
    }

    public function test_scope_for_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        Review::factory()->count(3)->create(['organization_id' => $org1->id]);
        Review::factory()->count(2)->create(['organization_id' => $org2->id]);

        $org1Reviews = Review::forOrganization($org1->id)->count();

        $this->assertEquals(3, $org1Reviews);
    }

    public function test_rating_is_cast_to_integer(): void
    {
        $review = Review::factory()->create(['rating' => 4]);

        $this->assertIsInt($review->rating);
        $this->assertEquals(4, $review->rating);
    }

    public function test_published_at_is_cast_to_datetime(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(Carbon::class, $review->published_at);
    }
}
