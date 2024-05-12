<?php

namespace App\Services;

use App\Events\TripProgressUpdated;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;

class TripBroadcastService
{
    public function __construct(
        private readonly TripEtaService $etaService
    ) {}

    public function broadcast(Trip $trip): void
    {
        if (! config('broadcasting.enabled', true)) {
            return;
        }

        try {
            $trip->refresh();
            $progress = $this->etaService->getRouteProgress($trip);

            TripProgressUpdated::dispatch($progress);
        } catch (\Throwable $e) {
            Log::warning('Trip broadcast failed (is Reverb running?)', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
