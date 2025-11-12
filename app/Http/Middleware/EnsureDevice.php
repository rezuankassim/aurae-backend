<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResource;
use App\Models\UserDevice;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class EnsureDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceUdid = $request->header('X-Device-Udid');

        if (empty($deviceUdid)) {
            return BaseResource::make(null)
                    ->setStatusCode(400)
                    ->setMessage('You need to specify your device details.')
                    ->response();
        }

        // We save the device details
        $device = UserDevice::updateOrCreate([
            'udid' => $deviceUdid,
        ], [
            'os' => $request->header('X-Device-OS'),
            'os_version' => $request->header('X-Device-OS-Version'),
            'manufacturer' => $request->header('X-Device-Manufacturer'),
            'model' => $request->header('X-Device-Model'),
            'fcm_token' => $request->header('X-Device-FCM-Token'),
            'app_version' => $request->header('X-Device-App-Version'),
        ]);

        $request->device = $device;

        return $next($request);
    }
}
