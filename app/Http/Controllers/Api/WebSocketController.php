<?php

namespace App\Http\Controllers\Api;

use App\Events\DevicePing;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    /**
     * Handle ping request from device
     */
    public function ping(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required|string',
        ]);

        $deviceUuid = $request->input('device_uuid');

        // Broadcast pong event back to the device
        broadcast(new DevicePing($deviceUuid))->toOthers();

        return BaseResource::make([
            'message' => 'pong',
            'timestamp' => now()->toIso8601String(),
        ])->additional([
            'status' => 200,
            'message' => 'Pong sent successfully',
        ]);
    }
}
