<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAppVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mobileAppVersion = $request->header('X-Device-App-Version');
        $tabletAppVersion = $request->header('X-Device-Tablet-App-Version');

        $mobile_apple_app_store_id = config('app.mobile_apple_app_store_id');
        $mobile_android_package_name = config('app.mobile_android_package_name');
        $tablet_android_package_name = config('app.tablet_android_package_name');

        $data = [
            'mobile_app_id' => $mobile_apple_app_store_id,
            'mobile_android_package_name' => $mobile_android_package_name,
            'tablet_android_package_name' => $tablet_android_package_name,
        ];

        // Check mobile app version if header is present
        if ($mobileAppVersion) {
            $requiredVersion = config('app.mobile_app_version');

            // Compare versions
            if (version_compare($mobileAppVersion, $requiredVersion, '<')) {
                return BaseResource::make($data)
                    ->additional([
                        'status' => 426,
                        'message' => 'Please update your app to the latest version to continue using this service.',
                        'current_version' => $mobileAppVersion,
                        'required_version' => $requiredVersion,
                        'update_required' => true,
                    ])
                    ->response()
                    ->setStatusCode(426); // 426 Upgrade Required
            }
        }

        // Check tablet app version if header is present
        if ($tabletAppVersion) {
            $requiredVersion = config('app.mobile_tablet_app_version');

            // Compare versions
            if (version_compare($tabletAppVersion, $requiredVersion, '<')) {
                return BaseResource::make($data)
                    ->additional([
                        'status' => 426,
                        'message' => 'Please update your app to the latest version to continue using this service.',
                        'current_version' => $tabletAppVersion,
                        'required_version' => $requiredVersion,
                        'update_required' => true,
                    ])
                    ->response()
                    ->setStatusCode(426); // 426 Upgrade Required
            }
        }

        return $next($request);
    }
}
