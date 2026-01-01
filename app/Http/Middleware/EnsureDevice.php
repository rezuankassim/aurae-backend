<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResource;
use App\Models\DeviceLocation;
use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
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
                ->additional([
                    'status' => 400,
                    'message' => 'You need to specify your device details.',
                ])
                ->response()
                ->setStatusCode(400);
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

        $this->logDeviceLocation($request, $device);

        return $next($request);
    }

    /**
     * Log the device's GPS location if coordinates are provided
     */
    protected function logDeviceLocation(Request $request, UserDevice $device): void
    {
        $latitude = $request->header('X-Device-Latitude');
        $longitude = $request->header('X-Device-Longitude');

        // Only log if we have at least latitude and longitude
        if (! $latitude || ! $longitude) {
            return;
        }

        DeviceLocation::create([
            'user_device_id' => $device->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $request->header('X-Device-Accuracy'),
            'altitude' => $request->header('X-Device-Altitude'),
            'speed' => $request->header('X-Device-Speed'),
            'heading' => $request->header('X-Device-Heading'),
            'api_endpoint' => $request->path(),
            'ip_address' => $request->ip(),
        ]);
    }
}
