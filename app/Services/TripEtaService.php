<?php

namespace App\Services;

use App\Models\Stop;
use App\Models\Trip;

class TripEtaService
{
    public function __construct(
        private readonly GeoService $geo,
        private readonly RoutingService $routing
    ) {}

    public function getNextStopEta(Trip $trip): ?array
    {
        $trip->loadMissing(['nextStop', 'latestLocation']);

        if (! $trip->nextStop || ! $trip->latestLocation) {
            return null;
        }

        return $this->buildEta(
            $trip->latestLocation->latitude,
            $trip->latestLocation->longitude,
            $trip->nextStop,
            $trip->latestLocation->speed_kmh
        );
    }

    public function getEtaToStop(Trip $trip, Stop $stop): ?array
    {
        $trip->loadMissing('latestLocation');

        if (! $trip->latestLocation) {
            return null;
        }

        if ($trip->current_stop_id && $stop->order <= ($trip->currentStop?->order ?? 0)) {
            return null;
        }

        return $this->buildEta(
            $trip->latestLocation->latitude,
            $trip->latestLocation->longitude,
            $stop,
            $trip->latestLocation->speed_kmh
        );
    }

    public function getRouteProgress(Trip $trip): array
    {
        $trip->loadMissing(['route.stops', 'currentStop', 'nextStop', 'latestLocation', 'driver.user']);

        $stops = $trip->route->stops;
        $nextStopEta = $this->getNextStopEta($trip);

        $stopEtas = $stops->map(function (Stop $stop) use ($trip) {
            $eta = $this->getEtaToStop($trip, $stop);

            return [
                'id' => $stop->id,
                'name' => $stop->name,
                'order' => $stop->order,
                'latitude' => $stop->latitude,
                'longitude' => $stop->longitude,
                'is_current' => $trip->current_stop_id === $stop->id,
                'is_next' => $trip->next_stop_id === $stop->id,
                'is_passed' => $trip->current_stop_id && $stop->order < ($trip->currentStop?->order ?? 0),
                'eta' => $eta,
            ];
        });

        return [
            'trip_id' => $trip->id,
            'driver_id' => $trip->driver_id,
            'status' => $trip->status,
            'driver' => [
                'name' => $trip->driver->user->name,
                'vehicle_plate' => $trip->driver->vehicle_plate,
                'vehicle_type' => $trip->driver->vehicle_type,
            ],
            'current_location' => $trip->latestLocation ? [
                'latitude' => $trip->latestLocation->latitude,
                'longitude' => $trip->latestLocation->longitude,
                'speed_kmh' => $trip->latestLocation->speed_kmh,
                'recorded_at' => $trip->latestLocation->recorded_at?->toIso8601String(),
            ] : null,
            'current_stop' => $trip->currentStop?->only(['id', 'name', 'order']),
            'next_stop' => $trip->nextStop ? array_merge(
                $trip->nextStop->only(['id', 'name', 'order']),
                ['eta' => $nextStopEta]
            ) : null,
            'stops' => $stopEtas,
        ];
    }

    private function buildEta(float $fromLat, float $fromLon, Stop $stop, ?float $speedKmh): array
    {
        $route = $this->routing->routeBetween($fromLat, $fromLon, $stop->latitude, $stop->longitude);
        $minutes = $route['duration_minutes'];

        if ($speedKmh && $route['source'] === 'haversine') {
            $minutes = $this->geo->estimateMinutes($route['distance_km'], $speedKmh);
        }

        return [
            'distance_km' => $route['distance_km'],
            'distance_formatted' => $this->geo->formatDistance($route['distance_km']),
            'estimated_minutes' => $minutes,
            'estimated_formatted' => $this->geo->formatDuration($minutes),
            'routing_source' => $route['source'],
            'speed_used_kmh' => $speedKmh ?? (float) config('services.route.default_average_speed_kmh', 25),
        ];
    }
}
