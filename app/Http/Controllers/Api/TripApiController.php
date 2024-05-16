<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\TripBroadcastService;
use App\Services\TripEtaService;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TripApiController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripEtaService $etaService,
        private readonly TripBroadcastService $broadcaster
    ) {}

    public function active(): JsonResponse
    {
        $trips = Trip::with(['driver.user', 'nextStop', 'latestLocation'])
            ->where('status', 'in_progress')
            ->get()
            ->map(fn (Trip $trip) => $this->etaService->getRouteProgress($trip));

        return response()->json(['data' => $trips]);
    }

    public function show(Trip $trip): JsonResponse
    {
        if (! $trip->isInProgress()) {
            return response()->json(['message' => 'Trip is not active.'], 404);
        }

        return response()->json(['data' => $this->etaService->getRouteProgress($trip)]);
    }

    public function updateLocation(Request $request, Trip $trip): JsonResponse
    {
        if (! $trip->isInProgress()) {
            return response()->json(['message' => 'Trip is not active.'], 422);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_kmh' => 'nullable|numeric|min:0|max:120',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $trip->locations()->create([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed_kmh' => $validated['speed_kmh'] ?? null,
            'heading' => $validated['heading'] ?? null,
            'recorded_at' => now(),
        ]);

        $this->broadcaster->broadcast($trip->fresh());

        return response()->json([
            'data' => $this->etaService->getRouteProgress($trip->fresh()),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
        ]);

        $driver = Driver::findOrFail($validated['driver_id']);
        $route = \App\Models\TransportRoute::where('is_active', true)->firstOrFail();

        try {
            $trip = $this->tripService->startTrip($driver, $route);
            $this->broadcaster->broadcast($trip);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $trip->load(['route.stops', 'nextStop'])], 201);
    }

    public function advance(Trip $trip): JsonResponse
    {
        try {
            $trip = $this->tripService->advanceToNextStop($trip);
            $this->broadcaster->broadcast($trip);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $this->etaService->getRouteProgress($trip)]);
    }
}
