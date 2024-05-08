<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassengerWaitlist extends Model
{
    protected $table = 'passenger_waitlist';

    protected $fillable = [
        'user_id',
        'passenger_name',
        'stop_id',
        'passenger_count',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }
}
