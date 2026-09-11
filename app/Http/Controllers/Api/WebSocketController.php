<?php

namespace App\Http\Controllers\Api;

use App\Events\DevicePing;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class WebSocketController extends Controller
{
    public function ping(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required|string',
        ]);

        $deviceUuid = $request->input('device_uuid');

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
