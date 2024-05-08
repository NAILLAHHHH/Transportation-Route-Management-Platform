<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends Model
{
    protected $fillable = [
        'driver_id',
        'transport_route_id',
        'status',
        'current_stop_id',
        'next_stop_id',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    public function currentStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'current_stop_id');
    }

    public function nextStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'next_stop_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class)->orderByDesc('recorded_at');
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(DriverLocation::class)->latestOfMany('recorded_at');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
}
