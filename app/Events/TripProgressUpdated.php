<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $progress) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('trips'),
            new Channel('trip.'.$this->progress['trip_id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TripProgressUpdated';
    }

    public function broadcastWith(): array
    {
        return ['progress' => $this->progress];
    }
}
