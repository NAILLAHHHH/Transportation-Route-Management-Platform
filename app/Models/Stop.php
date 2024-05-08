<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stop extends Model
{
    protected $fillable = [
        'transport_route_id',
        'name',
        'order',
        'latitude',
        'longitude',
        'is_pickup_point',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_pickup_point' => 'boolean',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function waitlist(): HasMany
    {
        return $this->hasMany(PassengerWaitlist::class);
    }

    public function waitingPassengers(): HasMany
    {
        return $this->waitlist()->where('status', 'waiting');
    }
}
