<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Stop;
use App\Models\TransportRoute;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TripService
{
    public function startTrip(Driver $driver, TransportRoute $route): Trip
    {
        if ($driver->activeTrip) {
            throw new InvalidArgumentException('Driver already has an active trip.');
        }

        $firstStop = $route->stops()->orderBy('order')->first();
        $secondStop = $route->stops()->orderBy('order')->skip(1)->first();

        return DB::transaction(function () use ($driver, $route, $firstStop, $secondStop) {
            $driver->update(['is_available' => false]);

            return Trip::create([
                'driver_id' => $driver->id,
                'transport_route_id' => $route->id,
                'status' => 'in_progress',
                'current_stop_id' => $firstStop?->id,
                'next_stop_id' => $secondStop?->id,
                'started_at' => now(),
            ]);
        });
    }

    public function advanceToNextStop(Trip $trip): Trip
    {
        if (! $trip->isInProgress()) {
            throw new InvalidArgumentException('Trip is not in progress.');
        }

        $stops = $trip->route->stops()->orderBy('order')->get();
        $arrivedStop = $trip->nextStop;

        if (! $arrivedStop) {
            return $this->completeTrip($trip);
        }

        $followingStop = $stops->firstWhere('order', '>', $arrivedStop->order);

        if (! $followingStop) {
            return $this->completeTrip($trip, $arrivedStop);
        }

        $trip->update([
            'current_stop_id' => $arrivedStop->id,
            'next_stop_id' => $followingStop->id,
        ]);

        return $trip->fresh(['currentStop', 'nextStop']);
    }

    public function completeTrip(Trip $trip, ?Stop $finalStop = null): Trip
    {
        $lastStop = $finalStop ?? $trip->route->stops()->orderByDesc('order')->first();

        $trip->update([
            'status' => 'completed',
            'current_stop_id' => $lastStop?->id,
            'next_stop_id' => null,
            'completed_at' => now(),
        ]);

        $trip->driver->update(['is_available' => true]);

        return $trip->fresh();
    }

    public function cancelTrip(Trip $trip): Trip
    {
        $trip->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        $trip->driver->update(['is_available' => true]);

        return $trip->fresh();
    }
}
