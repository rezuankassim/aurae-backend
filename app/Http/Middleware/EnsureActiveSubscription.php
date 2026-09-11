<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResource;
use App\Models\Device;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {

        if (! $request->hasHeader('X-Device-Tablet-App-Version')) {
            return $next($request);
        }

        $owner = $this->resolveDeviceOwner($request);

        if (! $owner) {

            return $next($request);
        }

        if (! $owner->activeSubscriptions()->exists()) {
            return BaseResource::make([
                'subscription_required' => true,
            ])
                ->additional([
                    'status' => 402,
                    'message' => 'Subscription expired or inactive. Please renew your subscription to continue using this service.',
                ])
                ->response()
                ->setStatusCode(402);
        }

        return $next($request);
    }

    protected function resolveDeviceOwner(Request $request): ?User
    {

        $user = $request->user();

        if ($user) {

            if ($user->isGuest()) {
                $guest = $user->guest;

                if ($guest && $guest->device) {
                    return $guest->device->user;
                }
            }

            return $user;
        }

        $deviceUuid = $request->input('device_uuid')
            ?? $request->input('uuid')
            ?? $request->header('X-Device-UUID');

        if ($deviceUuid) {
            $device = Device::where('uuid', $deviceUuid)->first();

            if ($device && $device->user) {
                return $device->user;
            }
        }

        return null;
    }
}
