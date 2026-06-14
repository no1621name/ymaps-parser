<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\ParseEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParseEventController extends Controller
{
    public function index(Organization $organization): StreamedResponse
    {
        $lastEventId = (int) request()->query('lastEventId', 0);

        return response()->stream(function () use ($organization, $lastEventId) {
            $maxDuration = 300;
            $start = time();
            $currentLastId = $lastEventId;

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                if ((time() - $start) > $maxDuration) {
                    echo "event: timeout\ndata: {}\n\n";
                    ob_flush();
                    flush();
                    break;
                }

                $events = ParseEvent::where('organization_id', $organization->id)
                    ->where('id', '>', $currentLastId)
                    ->orderBy('id')
                    ->get();

                foreach ($events as $event) {
                    $currentLastId = $event->id;

                    echo "id: {$event->id}\n";
                    echo "event: {$event->type}\n";
                    echo 'data: '.json_encode([
                        'type' => $event->type,
                        'payload' => $event->payload,
                        'created_at' => $event->created_at->toISOString(),
                    ])."\n\n";

                    ob_flush();
                    flush();
                }

                $org = $organization->fresh();

                if ($events->isNotEmpty() && ($org->status->value === 'done' || $org->status->value === 'failed')) {
                    echo "event: complete\ndata: {}\n\n";
                    ob_flush();
                    flush();
                    break;
                }

                if ($events->isEmpty()) {
                    echo ": heartbeat\n\n";
                    ob_flush();
                    flush();
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
