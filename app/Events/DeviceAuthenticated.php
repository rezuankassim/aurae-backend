<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeviceAuthenticated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $deviceUuid,
        public string $accessToken,
    ) {
        Log::info('DeviceAuthenticated event created', [
            'device_uuid' => $deviceUuid,
            'channel' => 'device.'.$deviceUuid,
            'token_length' => strlen($accessToken),
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('device.'.$this->deviceUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'device.authenticated';
    }

    public function broadcastWith(): array
    {
        return [
            'access_token' => $this->accessToken,
        ];
    }
}
