@extends('layouts.app')

@section('title', 'Driver Dashboard')

@section('content')
<h1 style="margin-bottom: 1.5rem;">Driver Dashboard</h1>

<div class="card">
    <h2>Registered Drivers</h2>
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($drivers as $driver)
                <tr>
                    <td>
                        <strong>{{ $driver->user->name }}</strong>
                        <div class="muted">{{ $driver->user->phone }}</div>
                    </td>
                    <td>{{ $driver->vehicle_plate }} ({{ $driver->vehicle_type }})</td>
                    <td>
                        @if($driver->activeTrip)
                            <span class="badge badge-active">On route</span>
                        @else
                            <span class="badge badge-idle">Available</span>
                        @endif
                    </td>
                    <td style="display: flex; gap: .5rem; flex-wrap: wrap;">
                        @if(!$driver->activeTrip)
                            <form action="{{ route('drivers.start', $driver) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">Start Trip</button>
                            </form>
                        @endif
                        <a href="{{ route('drivers.show', $driver) }}" class="btn btn-sm btn-outline">Manage</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($route)
<div class="card">
    <h3>Route: {{ $route->name }}</h3>
    <p class="muted">{{ $route->origin }} to {{ $route->destination }} — {{ $route->stops->count() }} stops</p>
</div>
@endif
@endsection
