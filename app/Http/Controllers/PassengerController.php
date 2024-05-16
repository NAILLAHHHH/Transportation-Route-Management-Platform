<?php

namespace App\Http\Controllers;

use App\Models\PassengerWaitlist;
use App\Models\Stop;
use App\Models\TransportRoute;
use App\Models\Trip;
use App\Services\TripEtaService;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    public function __construct(
        private readonly TripEtaService $etaService
    ) {}

    public function index()
    {
        $route = TransportRoute::with('stops')->where('is_active', true)->first();

        $stops = Stop::with(['route', 'waitingPassengers'])
            ->whereHas('route', fn ($q) => $q->where('is_active', true))
            ->orderBy('order')
            ->get();

        $activeTrips = Trip::with(['driver.user', 'nextStop', 'latestLocation'])
            ->where('status', 'in_progress')
            ->get()
            ->map(fn (Trip $trip) => $this->etaService->getRouteProgress($trip));

        return view('passenger.index', compact('stops', 'activeTrips', 'route'));
    }

    public function joinWaitlist(Request $request)
    {
        $validated = $request->validate([
            'stop_id' => 'required|exists:stops,id',
            'name' => 'required|string|max:100',
            'passenger_count' => 'required|integer|min:1|max:10',
        ]);

        PassengerWaitlist::create([
            'user_id' => auth()->id(),
            'passenger_name' => $validated['name'],
            'stop_id' => $validated['stop_id'],
            'passenger_count' => $validated['passenger_count'],
            'status' => 'waiting',
        ]);

        $stop = Stop::findOrFail($validated['stop_id']);

        return back()->with('success', 'You are on the waitlist at '.$stop->name.'.');
    }
}
