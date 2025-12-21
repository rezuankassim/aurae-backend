<?php

namespace App\Http\Controllers\Admin;

use App\Events\DeviceAuthenticated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WebSocketTestController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/WebSocketTest/Index', [
            'reverbConfig' => [
                'host' => config('reverb.apps.apps.0.options.host', 'localhost'),
                'port' => config('reverb.apps.apps.0.options.port', 8080),
                'key' => config('reverb.apps.apps.0.key'),
                'scheme' => config('reverb.apps.apps.0.options.scheme', 'http'),
            ],
        ]);
    }

    public function trigger(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required|string',
            'access_token' => 'nullable|string',
        ]);

        $deviceUuid = $request->input('device_uuid');
        $accessToken = $request->input('access_token', Str::random(60));

        broadcast(new DeviceAuthenticated($deviceUuid, $accessToken));

        return back()->with('websocket_trigger', [
            'success' => true,
            'message' => 'Event broadcasted successfully',
            'device_uuid' => $deviceUuid,
            'access_token' => $accessToken,
            'channel' => 'device.'.$deviceUuid,
            'event' => 'device.authenticated',
        ]);
    }
}
