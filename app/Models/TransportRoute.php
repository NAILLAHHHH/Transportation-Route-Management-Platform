<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    protected $fillable = [
        'name',
        'origin',
        'destination',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class)->orderBy('order');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function activeTrips(): HasMany
    {
        return $this->trips()->where('status', 'in_progress');
    }
}
