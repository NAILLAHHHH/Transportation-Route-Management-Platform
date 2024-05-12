<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutingService
{
    public function __construct(
        private readonly GeoService $geo
    ) {}

    public function routeBetween(float $fromLat, float $fromLon, float $toLat, float $toLon): array
    {
        $cacheKey = sprintf(
            'routing:%s:%s:%s:%s',
            round($fromLat, 4),
            round($fromLon, 4),
            round($toLat, 4),
            round($toLon, 4)
        );

        $ttl = (int) config('services.routing.cache_seconds', 60);

        return Cache::remember($cacheKey, $ttl, fn () => $this->fetchRoute($fromLat, $fromLon, $toLat, $toLon));
    }

    public function getRouteGeometry(Collection $stops): ?array
    {
        if ($stops->count() < 2) {
            return null;
        }

        $cacheKey = 'route_geometry:'.$stops->pluck('id')->implode('-');

        return Cache::remember($cacheKey, 3600, function () use ($stops) {
            $coordinates = $stops->map(fn ($stop) => "{$stop->longitude},{$stop->latitude}")->implode(';');
            $baseUrl = rtrim(config('services.routing.osrm_url'), '/');

            try {
                $response = Http::timeout(10)->get("{$baseUrl}/route/v1/driving/{$coordinates}", [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                ]);

                if ($response->successful()) {
                    $geometry = $response->json('routes.0.geometry.coordinates');

                    if ($geometry) {
                        return $this->flipCoordinates($geometry);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Route geometry fetch failed', ['error' => $e->getMessage()]);
            }

            return $stops->map(fn ($stop) => [$stop->latitude, $stop->longitude])->values()->all();
        });
    }

    private function fetchRoute(float $fromLat, float $fromLon, float $toLat, float $toLon): array
    {
        if ($orsKey = config('services.routing.ors_api_key')) {
            $ors = $this->fetchFromOpenRouteService($fromLat, $fromLon, $toLat, $toLon, $orsKey);
            if ($ors) {
                return $ors;
            }
        }

        $osrm = $this->fetchFromOsrm($fromLat, $fromLon, $toLat, $toLon);
        if ($osrm) {
            return $osrm;
        }

        return $this->fallbackRoute($fromLat, $fromLon, $toLat, $toLon);
    }

    private function fetchFromOsrm(float $fromLat, float $fromLon, float $toLat, float $toLon): ?array
    {
        $baseUrl = rtrim(config('services.routing.osrm_url'), '/');
        $coordinates = "{$fromLon},{$fromLat};{$toLon},{$toLat}";

        try {
            $response = Http::timeout(8)->get("{$baseUrl}/route/v1/driving/{$coordinates}", [
                'overview' => 'false',
            ]);

            if ($response->successful()) {
                $route = $response->json('routes.0');

                if ($route) {
                    return [
                        'distance_km' => round($route['distance'] / 1000, 2),
                        'duration_minutes' => max(1, (int) round($route['duration'] / 60)),
                        'source' => 'osrm',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OSRM routing failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function fetchFromOpenRouteService(float $fromLat, float $fromLon, float $toLat, float $toLon, string $apiKey): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders(['Authorization' => $apiKey])
                ->post('https://api.openrouteservice.org/v2/directions/driving-car', [
                    'coordinates' => [[$fromLon, $fromLat], [$toLon, $toLat]],
                ]);

            if ($response->successful()) {
                $summary = $response->json('features.0.properties.summary');

                if ($summary) {
                    return [
                        'distance_km' => round($summary['distance'] / 1000, 2),
                        'duration_minutes' => max(1, (int) round($summary['duration'] / 60)),
                        'source' => 'openrouteservice',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OpenRouteService routing failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function fallbackRoute(float $fromLat, float $fromLon, float $toLat, float $toLon): array
    {
        $distanceKm = $this->geo->distanceKm($fromLat, $fromLon, $toLat, $toLon);

        return [
            'distance_km' => $distanceKm,
            'duration_minutes' => $this->geo->estimateMinutes($distanceKm),
            'source' => 'haversine',
        ];
    }

    private function flipCoordinates(array $coordinates): array
    {
        return array_map(fn (array $pair) => [$pair[1], $pair[0]], $coordinates);
    }
}
