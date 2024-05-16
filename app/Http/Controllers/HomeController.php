<?php

namespace App\Http\Controllers;

use App\Models\TransportRoute;
use App\Models\Trip;
use App\Services\TripEtaService;

class HomeController extends Controller
{
    public function __construct(
        private readonly TripEtaService $etaService
    ) {}

    public function index()
    {
        $route = TransportRoute::with('stops')
            ->where('is_active', true)
            ->first();

        $activeTrips = Trip::with(['driver.user', 'nextStop', 'latestLocation'])
            ->where('status', 'in_progress')
            ->latest()
            ->get();

        $activeTripsProgress = $activeTrips
            ->map(fn (Trip $trip) => $this->etaService->getRouteProgress($trip))
            ->values();

        return view('home', compact('route', 'activeTrips', 'activeTripsProgress'));
    }
}
