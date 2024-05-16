<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TransportRoute;
use App\Services\TripBroadcastService;
use App\Services\TripEtaService;
use App\Services\TripService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class DriverDashboardController extends Controller
{
    public function __construct(
        private readonly TripService $tripService,
        private readonly TripEtaService $etaService,
        private readonly TripBroadcastService $broadcaster
    ) {}

    public function index()
    {
        $user = auth()->user();
        $route = TransportRoute::with('stops')->where('is_active', true)->first();

        if ($user->role === 'driver' && $user->driver) {
            return redirect()->route('drivers.show', $user->driver);
        }

        $drivers = Driver::with(['user', 'activeTrip.nextStop', 'activeTrip.latestLocation'])->get();

        return view('driver.dashboard', compact('route', 'drivers'));
    }

    public function show(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $driver->load(['user', 'activeTrip.route.stops', 'activeTrip.nextStop', 'activeTrip.latestLocation']);
        $route = TransportRoute::with('stops')->where('is_active', true)->first();

        $progress = $driver->activeTrip
            ? $this->etaService->getRouteProgress($driver->activeTrip)
            : null;

        return view('driver.show', compact('driver', 'progress', 'route'));
    }

    public function startTrip(Request $request, Driver $driver)
    {
        $this->authorizeDriver($driver);

        $route = TransportRoute::where('is_active', true)->firstOrFail();

        try {
            $trip = $this->tripService->startTrip($driver, $route);
            $this->broadcaster->broadcast($trip);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('drivers.show', $driver)
            ->with('success', 'Trip started from Nyabugogo.');
    }

    public function advanceStop(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $trip = $driver->activeTrip;

        if (! $trip) {
            return back()->with('error', 'No active trip found.');
        }

        $trip = $this->tripService->advanceToNextStop($trip);
        $this->broadcaster->broadcast($trip);

        $message = $trip->status === 'completed'
            ? 'Trip completed at Kimironko.'
            : 'Arrived at stop. Heading to '.$trip->nextStop?->name;

        return back()->with('success', $message);
    }

    public function updateLocation(Request $request, Driver $driver)
    {
        $this->authorizeDriver($driver);

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_kmh' => 'nullable|numeric|min:0|max:120',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $trip = $driver->activeTrip;

        if (! $trip) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Start a trip before updating location.'], 422);
            }

            return back()->with('error', 'Start a trip before updating location.');
        }

        $trip->locations()->create([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed_kmh' => $validated['speed_kmh'] ?? null,
            'heading' => $validated['heading'] ?? null,
            'recorded_at' => now(),
        ]);

        $this->broadcaster->broadcast($trip->fresh());

        $progress = $this->etaService->getRouteProgress($trip->fresh());

        if ($request->expectsJson()) {
            return response()->json(['data' => $progress]);
        }

        return back()->with('success', 'Location updated.');
    }

    private function authorizeDriver(Driver $driver): void
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return;
        }

        if ($user->role === 'driver' && $user->driver?->id === $driver->id) {
            return;
        }

        abort(403);
    }
}
