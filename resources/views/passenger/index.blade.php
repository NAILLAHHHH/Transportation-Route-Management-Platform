@extends('layouts.app')

@section('title', 'Passenger View')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1>Passenger Coordination</h1>
    <span class="live-indicator">Live via WebSocket</span>
</div>

@if($route)
<div class="card">
    <h2>Live Route Map</h2>
    <p class="muted" style="margin-bottom: 1rem;">Track drivers in real time on the Nyabugogo → Kimironko route.</p>
    <div id="route-map" class="map-container"
         data-stops='@json($route->stops)'
         data-drivers='@json($activeTrips)'
         data-geometry-url="{{ url('/api/routes/'.$route->id.'/geometry') }}"
         data-live="true">
    </div>
</div>
@endif

<div class="grid grid-2">
    <div class="card">
        <h2>Join Waitlist at a Stop</h2>
        <p class="muted">Let drivers know you're waiting for pickup.</p>
        <form action="{{ route('passengers.waitlist') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Your Name</label>
                <input type="text" name="name" value="{{ auth()->user()->name }}" required>
            </div>
            <div class="form-group">
                <label>Pickup Stop</label>
                <select name="stop_id" required>
                    @foreach($stops as $stop)
                        <option value="{{ $stop->id }}">{{ $stop->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Number of Passengers</label>
                <input type="number" name="passenger_count" min="1" max="10" value="1" required>
            </div>
            <button type="submit" class="btn btn-primary">Join Waitlist</button>
        </form>
    </div>

    <div class="card">
        <h2>Waiting at Stops</h2>
        <table>
            <thead>
                <tr>
                    <th>Stop</th>
                    <th>Waiting</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stops as $stop)
                    <tr>
                        <td>{{ $stop->name }}</td>
                        <td>{{ $stop->waitingPassengers->sum('passenger_count') }} passengers</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Live Driver ETAs</h2>
    <div id="active-trips">
        @forelse($activeTrips as $trip)
            @include('partials.trip-eta', ['trip' => $trip])
        @empty
            <p class="muted" id="no-trips-msg">No drivers are currently on the route. Check back soon.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
    function renderTripCard(trip) {
        const nextEta = trip.next_stop?.eta;
        const stopsHtml = (trip.stops || [])
            .filter(s => !s.is_passed && s.eta)
            .map(s => `
                <li>
                    <span class="stop-number ${s.is_next ? 'current' : ''}">${s.order}</span>
                    <div>
                        <strong>${s.name}</strong>
                        <div class="muted">${s.eta.distance_formatted} · ${s.eta.estimated_formatted} <small>(${s.eta.routing_source})</small></div>
                    </div>
                </li>
            `).join('');

        return `
            <div class="trip-card" data-trip-id="${trip.trip_id}" style="padding: 1.25rem 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <strong>${trip.driver.name}</strong>
                        <span class="badge badge-active">En route</span>
                        <div class="muted">${trip.driver.vehicle_plate}</div>
                    </div>
                    ${nextEta ? `
                    <div class="eta-box" style="margin: 0; text-align: right;">
                        <div class="muted">→ ${trip.next_stop.name}</div>
                        <div class="value">${nextEta.distance_formatted}</div>
                        <div><strong>${nextEta.estimated_formatted}</strong></div>
                    </div>` : ''}
                </div>
                <ul class="stop-list" style="margin-top: 1rem;">${stopsHtml}</ul>
            </div>
        `;
    }

    document.addEventListener('trip-progress-updated', (e) => {
        const progress = e.detail;
        const container = document.getElementById('active-trips');
        const noTrips = document.getElementById('no-trips-msg');
        if (noTrips) noTrips.remove();

        let card = container.querySelector(`[data-trip-id="${progress.trip_id}"]`);
        const html = renderTripCard(progress);

        if (card) {
            card.outerHTML = html;
        } else {
            container.insertAdjacentHTML('afterbegin', html);
        }

        window.routeMap?.updateFromProgress(progress);
    });
</script>
@endpush
