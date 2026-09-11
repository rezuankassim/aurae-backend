<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DevicePing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $deviceUuid,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('device.'.$this->deviceUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.pong';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => 'pong',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
