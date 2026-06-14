<?php

namespace Tests\Unit;

use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use App\Services\YandexMaps\ParserOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParseOrganizationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_unique_id_format(): void
    {
        $org = Organization::factory()->create();
        $job = new ParseOrganizationJob($org->id);

        $this->assertEquals("parse-org-{$org->id}", $job->uniqueId());
    }

    public function test_handle_skips_when_organization_not_found(): void
    {
        $orchestrator = $this->mock(ParserOrchestrator::class);
        $orchestrator->shouldNotReceive('parse');

        $job = new ParseOrganizationJob(99999);
        $job->handle($orchestrator);

        $this->assertTrue(true);
    }

    public function test_handle_skips_when_parsing_not_allowed(): void
    {
        $org = Organization::factory()->create(['status' => 'parsing']);
        $orchestrator = $this->mock(ParserOrchestrator::class);
        $orchestrator->shouldNotReceive('parse');

        $job = new ParseOrganizationJob($org->id);
        $job->handle($orchestrator);

        $this->assertTrue(true);
    }

    public function test_handle_calls_orchestrator_when_allowed(): void
    {
        $org = Organization::factory()->create([
            'status' => 'pending',
            'business_id' => '41332984866',
        ]);
        $orchestrator = $this->mock(ParserOrchestrator::class);
        $orchestrator->shouldReceive('parse')
            ->once()
            ->withArgs(fn ($o) => $o->id === $org->id);

        $job = new ParseOrganizationJob($org->id);
        $job->handle($orchestrator);
    }

    public function test_job_properties(): void
    {
        $job = new ParseOrganizationJob(123);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(2, $job->maxExceptions);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals(3600, $job->uniqueFor);
    }

    public function test_failed_marks_organization_as_failed(): void
    {
        $org = Organization::factory()->create([
            'status' => 'parsing',
            'business_id' => '41332984866',
        ]);

        $job = new ParseOrganizationJob($org->id);
        $job->failed(new \Exception('Test error'));

        $fresh = $org->fresh();
        $this->assertEquals('failed', $fresh->status->value);
        $this->assertEquals('Test error', $fresh->error_message);
    }

    public function test_failed_handles_missing_organization(): void
    {
        $job = new ParseOrganizationJob(99999);

        $job->failed(new \Exception('Test error'));

        $this->assertTrue(true);
    }
}
