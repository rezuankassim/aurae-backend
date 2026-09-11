<?php

namespace App\Http\Middleware;

use App\Http\Resources\BaseResource;
use App\Models\GeneralSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class CheckAppVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $mobileAppVersion = $request->header('X-Device-App-Version');
        $tabletAppVersion = $request->header('X-Device-Tablet-App-Version');
        $generalSetting = Cache::remember('general_setting', 300, fn () => GeneralSetting::first());

        if ($mobileAppVersion && $generalSetting && $generalSetting->apk_version) {
            $requiredVersion = $generalSetting->apk_version;

            if (version_compare($mobileAppVersion, $requiredVersion, '<')) {
                $downloadUrl = $generalSetting->apk_file_path
                    ? URL::temporarySignedRoute('api.apk.download', now()->addMinutes(30))
                    : '';

                $data = [
                    'version' => $requiredVersion,
                    'message' => $generalSetting->apk_release_notes ?? 'Please update your app to the latest version to continue using this service.',
                    'mobile_app_id' => config('app.mobile_apple_app_store_id', ''),
                    'mobile_android_package_name' => config('app.mobile_android_package_name', ''),
                    'mobile_android_url' => $downloadUrl,
                ];

                return BaseResource::make($data)
                    ->additional([
                        'status' => 426,
                        'message' => 'Please update your app to the latest version to continue using this service.',
                    ])
                    ->response()
                    ->setStatusCode(426);
            }
        }

        if ($tabletAppVersion && $generalSetting && $generalSetting->tablet_apk_version) {
            $requiredVersion = $generalSetting->tablet_apk_version;

            if (version_compare($tabletAppVersion, $requiredVersion, '<')) {
                $downloadUrl = $generalSetting->tablet_apk_file_path
                    ? URL::temporarySignedRoute('api.apk.tablet.download', now()->addMinutes(30))
                    : '';

                $data = [
                    'version' => $requiredVersion,
                    'message' => $generalSetting->tablet_apk_release_notes ?? 'Please update your app to the latest version to continue using this service.',
                    'mobile_app_id' => config('app.mobile_apple_app_store_id', ''),
                    'tablet_android_package_name' => config('app.tablet_android_package_name', ''),
                    'tablet_android_url' => $downloadUrl,
                ];

                return BaseResource::make($data)
                    ->additional([
                        'status' => 426,
                        'message' => 'Please update your app to the latest version to continue using this service.',
                    ])
                    ->response()
                    ->setStatusCode(426);
            }
        }

        return $next($request);
    }
}
