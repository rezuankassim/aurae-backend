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
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check subscription for tablet requests
        if (! $request->hasHeader('X-Device-Tablet-App-Version')) {
            return $next($request);
        }

        $owner = $this->resolveDeviceOwner($request);

        if (! $owner) {
            // No owner found - allow request to proceed
            // Other middleware/controllers will handle auth
            return $next($request);
        }

        // Check if the owner has at least one active subscription
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

    /**
     * Resolve the device owner from the request.
     */
    protected function resolveDeviceOwner(Request $request): ?User
    {
        // First, check if there's an authenticated user
        $user = $request->user();

        if ($user) {
            // If user is a guest, get the device owner
            if ($user->isGuest()) {
                $guest = $user->guest;

                if ($guest && $guest->device) {
                    return $guest->device->user;
                }
            }

            // If user is not a guest, they are the main user
            return $user;
        }

        // No authenticated user - try to find device owner from request parameters
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
