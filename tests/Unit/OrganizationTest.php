<?php

namespace Tests\Unit;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_parsing_allowed_pending(): void
    {
        $org = Organization::factory()->create(['status' => OrganizationStatus::Pending]);

        $this->assertTrue($org->isParsingAllowed());
    }

    public function test_is_parsing_allowed_parsing(): void
    {
        $org = Organization::factory()->create(['status' => OrganizationStatus::Parsing]);

        $this->assertFalse($org->isParsingAllowed());
    }

    public function test_is_parsing_allowed_done_within_rate_limit(): void
    {
        $org = Organization::factory()->create([
            'status' => OrganizationStatus::Done,
            'parsed_at' => now()->subMinutes(30),
        ]);

        $this->assertFalse($org->isParsingAllowed());
    }

    public function test_is_parsing_allowed_done_after_rate_limit(): void
    {
        $org = Organization::factory()->create([
            'status' => OrganizationStatus::Done,
            'parsed_at' => now()->subMinutes(61),
        ]);

        $this->assertTrue($org->isParsingAllowed());
    }

    public function test_is_parsing_allowed_done_no_parsed_at(): void
    {
        $org = Organization::factory()->create([
            'status' => OrganizationStatus::Done,
            'parsed_at' => null,
        ]);

        $this->assertTrue($org->isParsingAllowed());
    }

    public function test_is_parsing_allowed_failed(): void
    {
        $org = Organization::factory()->create(['status' => OrganizationStatus::Failed]);

        $this->assertTrue($org->isParsingAllowed());
    }

    public function test_mark_as_parsing(): void
    {
        $org = Organization::factory()->create(['status' => OrganizationStatus::Pending]);

        $org->markAsParsing();

        $this->assertEquals(OrganizationStatus::Parsing, $org->fresh()->status);
    }

    public function test_mark_as_done(): void
    {
        $org = Organization::factory()->create(['status' => OrganizationStatus::Parsing]);

        $org->markAsDone();

        $fresh = $org->fresh();
        $this->assertEquals(OrganizationStatus::Done, $fresh->status);
        $this->assertNotNull($fresh->parsed_at);
        $this->assertNull($fresh->error_message);
    }

    public function test_mark_as_failed(): void
    {
        $org = Organization::factory()->create(['status' => OrganizationStatus::Parsing]);

        $org->markAsFailed('Test error message');

        $fresh = $org->fresh();
        $this->assertEquals(OrganizationStatus::Failed, $fresh->status);
        $this->assertEquals('Test error message', $fresh->error_message);
    }

    public function test_scope_pending(): void
    {
        Organization::factory()->count(2)->create(['status' => OrganizationStatus::Pending]);
        Organization::factory()->count(3)->create(['status' => OrganizationStatus::Done]);

        $pending = Organization::pending()->count();

        $this->assertEquals(2, $pending);
    }

    public function test_scope_needs_update_done_with_old_parsed_at(): void
    {
        Organization::factory()->create([
            'status' => OrganizationStatus::Done,
            'parsed_at' => now()->subMinutes(61),
        ]);
        Organization::factory()->create([
            'status' => OrganizationStatus::Done,
            'parsed_at' => now()->subMinutes(30),
        ]);
        Organization::factory()->create([
            'status' => OrganizationStatus::Pending,
        ]);

        $needsUpdate = Organization::needsUpdate()->count();

        $this->assertEquals(1, $needsUpdate);
    }

    public function test_scope_needs_update_custom_minutes(): void
    {
        Organization::factory()->create([
            'status' => OrganizationStatus::Done,
            'parsed_at' => now()->subMinutes(31),
        ]);

        $needsUpdateDefault = Organization::needsUpdate()->count();
        $needsUpdate30 = Organization::needsUpdate(30)->count();

        $this->assertEquals(0, $needsUpdateDefault);
        $this->assertEquals(1, $needsUpdate30);
    }

    public function test_reviews_relation(): void
    {
        $org = Organization::factory()->create();
        $review = Review::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($org->reviews->contains($review));
    }

    public function test_latest_review_relation(): void
    {
        $org = Organization::factory()->create();

        $oldReview = Review::factory()->create([
            'organization_id' => $org->id,
            'published_at' => now()->subDays(2),
        ]);
        $newReview = Review::factory()->create([
            'organization_id' => $org->id,
            'published_at' => now(),
        ]);

        $this->assertEquals($newReview->id, $org->latestReview->first()->id);
    }
}
