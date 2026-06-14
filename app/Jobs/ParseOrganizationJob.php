<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\YandexMaps\ParserOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ParseOrganizationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public int $timeout = 300;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $organizationId,
    ) {}

    public function uniqueId(): string
    {
        return "parse-org-{$this->organizationId}";
    }

    public function handle(ParserOrchestrator $orchestrator): void
    {
        $organization = Organization::find($this->organizationId);

        if (! $organization || ! $organization->isParsingAllowed()) {
            return;
        }

        $orchestrator->parse($organization);
    }

    public function failed(?Throwable $exception): void
    {
        $organization = Organization::find($this->organizationId);

        if (! $organization) {
            return;
        }

        $organization->markAsFailed($exception?->getMessage() ?? 'Unknown error');
    }
}
