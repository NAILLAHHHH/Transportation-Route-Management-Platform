<?php

namespace Tests\Unit;

use App\Services\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceTest extends TestCase
{
    public function test_calculates_distance_between_kigali_stops(): void
    {
        $geo = new GeoService;

        $distance = $geo->distanceKm(-1.9395, 30.0588, -1.9260, 30.1065);

        $this->assertGreaterThan(4, $distance);
        $this->assertLessThan(8, $distance);
    }

    public function test_estimates_travel_minutes(): void
    {
        $geo = new GeoService;

        $minutes = $geo->estimateMinutes(5.0, 25);

        $this->assertSame(12, $minutes);
    }
}
