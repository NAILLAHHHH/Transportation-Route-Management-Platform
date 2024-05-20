<div class="trip-card" data-trip-id="{{ $trip['trip_id'] }}" style="padding: 1.25rem 0; border-bottom: 1px solid var(--border);">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <strong>{{ $trip['driver']['name'] }}</strong>
            <span class="badge badge-active">En route</span>
            <div class="muted">{{ $trip['driver']['vehicle_plate'] }}</div>
        </div>
        @if($trip['next_stop'] && ($trip['next_stop']['eta'] ?? null))
            <div class="eta-box" style="margin: 0; text-align: right;">
                <div class="muted">→ {{ $trip['next_stop']['name'] }}</div>
                <div class="value">{{ $trip['next_stop']['eta']['distance_formatted'] }}</div>
                <div><strong>{{ $trip['next_stop']['eta']['estimated_formatted'] }}</strong></div>
            </div>
        @endif
    </div>

    <ul class="stop-list" style="margin-top: 1rem;">
        @foreach($trip['stops'] as $stop)
            @if(!$stop['is_passed'] && ($stop['eta'] ?? null))
                <li>
                    <span class="stop-number {{ $stop['is_next'] ? 'current' : '' }}">{{ $stop['order'] }}</span>
                    <div>
                        <strong>{{ $stop['name'] }}</strong>
                        <div class="muted">{{ $stop['eta']['distance_formatted'] }} · {{ $stop['eta']['estimated_formatted'] }} <small>({{ $stop['eta']['routing_source'] }})</small></div>
                    </div>
                </li>
            @endif
        @endforeach
    </ul>
</div>
