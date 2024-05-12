<?php

namespace App\Services;

class GeoService
{
    private const EARTH_RADIUS_KM = 6371;

    public function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_KM * $c, 2);
    }

    public function estimateMinutes(float $distanceKm, ?float $speedKmh = null): int
    {
        $speed = $speedKmh ?? (float) config('services.route.default_average_speed_kmh', 25);

        if ($speed <= 0) {
            $speed = 25;
        }

        return (int) max(1, round(($distanceKm / $speed) * 60));
    }

    public function formatDistance(float $km): string
    {
        if ($km < 1) {
            return round($km * 1000).' m';
        }

        return number_format($km, 1).' km';
    }

    public function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        return $remaining > 0 ? "{$hours}h {$remaining}min" : "{$hours}h";
    }
}
