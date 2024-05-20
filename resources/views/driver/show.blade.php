@extends('layouts.app')

@section('title', $driver->user->name . ' - Trip')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1>{{ $driver->user->name }}</h1>
        <p class="muted">{{ $driver->vehicle_plate }} · {{ $driver->vehicle_type }}</p>
    </div>
    <a href="{{ route('drivers.index') }}" class="btn btn-outline">← Back</a>
</div>

@if($route)
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Live Route Map</h2>
        @if($driver->activeTrip)
            <span class="live-indicator">Live via WebSocket</span>
        @endif
    </div>
    <div id="route-map" class="map-container"
         data-stops='@json($route->stops)'
         data-drivers='@json($progress ? [$progress] : [])'
         data-geometry-url="{{ url('/api/routes/'.$route->id.'/geometry') }}"
         data-live="{{ $driver->activeTrip ? 'true' : 'false' }}"
         data-trip-id="{{ $driver->activeTrip?->id }}">
    </div>
</div>
@endif

@if($driver->activeTrip)
    @php $trip = $driver->activeTrip; @endphp

    <div class="grid grid-2">
        <div class="card">
            <h2>Current Trip</h2>
            <p>Status: <span class="badge badge-active">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span></p>
            <p class="muted">Started {{ $trip->started_at?->diffForHumans() }}</p>

            <div id="next-stop-eta">
                @if($progress && $progress['next_stop'])
                    <div class="eta-box">
                        <div class="muted">Next stop: <span id="next-stop-name">{{ $progress['next_stop']['name'] }}</span></div>
                        @if($progress['next_stop']['eta'])
                            <div class="value" id="next-stop-distance">{{ $progress['next_stop']['eta']['distance_formatted'] }}</div>
                            <div><strong id="next-stop-time">{{ $progress['next_stop']['eta']['estimated_formatted'] }}</strong> estimated</div>
                            <div class="muted" id="next-stop-source">via {{ $progress['next_stop']['eta']['routing_source'] ?? 'road' }} routing</div>
                        @endif
                    </div>
                @endif
            </div>

            <form action="{{ route('drivers.advance', $driver) }}" method="POST" style="margin-top: 1rem;">
                @csrf
                <button type="submit" class="btn btn-accent">Mark Arrival at Next Stop</button>
            </form>
        </div>

        <div class="card">
            <h2>Update GPS Location</h2>
            <p class="muted">Share your position — updates broadcast instantly to passengers.</p>
            <form id="location-form">
                @csrf
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" required
                           value="{{ $trip->latestLocation?->latitude ?? -1.9395 }}">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" required
                           value="{{ $trip->latestLocation?->longitude ?? 30.0588 }}">
                </div>
                <div class="form-group">
                    <label>Speed (km/h)</label>
                    <input type="number" step="any" name="speed_kmh" id="speed_kmh"
                           value="{{ $trip->latestLocation?->speed_kmh ?? 25 }}">
                </div>
                <button type="submit" class="btn btn-primary">Update Location</button>
                <button type="button" class="btn btn-outline" id="use-gps" style="margin-left: .5rem;">Use My GPS</button>
            </form>
            <p id="location-status" class="muted" style="margin-top: .75rem;"></p>
        </div>
    </div>

    @if($progress)
    <div class="card">
        <h2>Route Progress</h2>
        <ul class="stop-list" id="stop-progress-list">
            @foreach($progress['stops'] as $stop)
                <li data-stop-id="{{ $stop['id'] }}">
                    <span class="stop-number {{ $stop['is_passed'] ? 'passed' : '' }} {{ $stop['is_current'] ? 'current' : '' }}">
                        {{ $stop['order'] }}
                    </span>
                    <div style="flex: 1;">
                        <strong>{{ $stop['name'] }}</strong>
                        @if($stop['is_next'])
                            <span class="badge badge-active">Next</span>
                        @endif
                        @if($stop['eta'] && !$stop['is_passed'])
                            <div class="muted stop-eta">{{ $stop['eta']['distance_formatted'] }} · {{ $stop['eta']['estimated_formatted'] }} <small>({{ $stop['eta']['routing_source'] }})</small></div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
@else
    <div class="card">
        <h2>No Active Trip</h2>
        <p class="muted">Start a trip from Nyabugogo to begin coordinating passengers.</p>
        <form action="{{ route('drivers.start', $driver) }}" method="POST" style="margin-top: 1rem;">
            @csrf
            <button type="submit" class="btn btn-primary">Start Trip from Nyabugogo</button>
        </form>
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.getElementById('use-gps')?.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }
        navigator.geolocation.getCurrentPosition(function (pos) {
            document.getElementById('latitude').value = pos.coords.latitude.toFixed(6);
            document.getElementById('longitude').value = pos.coords.longitude.toFixed(6);
            if (pos.coords.speed) {
                document.getElementById('speed_kmh').value = (pos.coords.speed * 3.6).toFixed(1);
            }
        }, function () {
            alert('Unable to retrieve your location.');
        });
    });

    document.getElementById('location-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const status = document.getElementById('location-status');
        status.textContent = 'Updating...';

        const formData = new FormData(this);
        const response = await fetch('{{ route('drivers.location', $driver) }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        if (response.ok) {
            status.textContent = 'Location updated and broadcast to passengers.';
            const json = await response.json();
            if (json.data) updateProgressUI(json.data);
        } else {
            status.textContent = 'Failed to update location.';
        }
    });

    function updateProgressUI(progress) {
        if (progress.next_stop?.eta) {
            document.getElementById('next-stop-name').textContent = progress.next_stop.name;
            document.getElementById('next-stop-distance').textContent = progress.next_stop.eta.distance_formatted;
            document.getElementById('next-stop-time').textContent = progress.next_stop.eta.estimated_formatted;
            document.getElementById('next-stop-source').textContent = 'via ' + progress.next_stop.eta.routing_source + ' routing';
        }

        progress.stops?.forEach(stop => {
            const li = document.querySelector(`[data-stop-id="${stop.id}"]`);
            if (!li) return;
            const num = li.querySelector('.stop-number');
            num.classList.toggle('passed', stop.is_passed);
            num.classList.toggle('current', stop.is_current);
            const etaEl = li.querySelector('.stop-eta');
            if (etaEl && stop.eta && !stop.is_passed) {
                etaEl.innerHTML = `${stop.eta.distance_formatted} · ${stop.eta.estimated_formatted} <small>(${stop.eta.routing_source})</small>`;
            }
        });

        window.routeMap?.updateFromProgress(progress);
    }

    document.addEventListener('trip-progress-updated', (e) => updateProgressUI(e.detail));
</script>
@endpush
