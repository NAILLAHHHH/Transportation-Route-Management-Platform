@extends('layouts.app')

@section('title', 'Kigali Route Manager')

@section('content')
<div class="hero">
    <h1>Nyabugogo → Kimironko</h1>
    <p>Real-time maps, road routing ETAs, and WebSocket updates for drivers and passengers.</p>
</div>

@if($route)
<div class="card">
    <h2>Route Map</h2>
    <div id="route-map" class="map-container map-sm"
         data-stops='@json($route->stops)'
         data-drivers='@json($activeTripsProgress ?? [])'
         data-geometry-url="{{ url('/api/routes/'.$route->id.'/geometry') }}"
         data-live="true">
    </div>
</div>
@endif

<div class="grid grid-2">
    <div class="card">
        <h2>Route Overview</h2>
        @if($route)
            <p><strong>{{ $route->name }}</strong></p>
            <p class="muted">{{ $route->description }}</p>
            <ul class="stop-list" style="margin-top: 1rem;">
                @foreach($route->stops as $stop)
                    <li>
                        <span class="stop-number">{{ $stop->order }}</span>
                        <div>
                            <strong>{{ $stop->name }}</strong>
                            @if($loop->first)
                                <div class="muted">Origin</div>
                            @elseif($loop->last)
                                <div class="muted">Destination</div>
                            @else
                                <div class="muted">Pickup stop</div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted">No active route configured. Run database seeders.</p>
        @endif
    </div>

    <div class="card">
        <h2>Active Trips</h2>
        @forelse($activeTrips as $trip)
            <div style="padding: 1rem 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>{{ $trip->driver->user->name }}</strong>
                        <span class="badge badge-active">In progress</span>
                        <div class="muted">{{ $trip->driver->vehicle_plate }}</div>
                    </div>
                    @auth
                        @if(in_array(auth()->user()->role, ['driver', 'admin']))
                            <a href="{{ route('drivers.show', $trip->driver) }}" class="btn btn-sm btn-outline">Track</a>
                        @endif
                    @endauth
                </div>
                @if($trip->nextStop)
                    <div class="eta-box">
                        <div class="muted">Next stop: {{ $trip->nextStop->name }}</div>
                    </div>
                @endif
            </div>
        @empty
            <p class="muted">No active trips. Drivers can start from the Drivers dashboard.</p>
        @endforelse

        <div style="margin-top: 1.5rem; display: flex; gap: .75rem; flex-wrap: wrap;">
            @auth
                @if(in_array(auth()->user()->role, ['driver', 'admin']))
                    <a href="{{ route('drivers.index') }}" class="btn btn-primary">Driver Dashboard</a>
                @endif
                @if(in_array(auth()->user()->role, ['passenger', 'admin']))
                    <a href="{{ route('passengers.index') }}" class="btn btn-accent">Passenger View</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Login to get started</a>
            @endauth
        </div>
    </div>
</div>
@endsection
