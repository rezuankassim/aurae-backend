<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ApkController extends Controller
{
    /**
     * Get APK file information
     */
    public function info()
    {
        $generalSetting = GeneralSetting::first();

        if (! $generalSetting || ! $generalSetting->apk_file_path) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'APK file not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        // Short-lived signed URL so the APK is not exposed at a permanent public URL.
        $downloadUrl = URL::temporarySignedRoute('api.apk.download', now()->addMinutes(30));

        return BaseResource::make([
            'version' => $generalSetting->apk_version,
            'message' => $generalSetting->apk_release_notes ?? '',
            'mobile_app_id' => config('app.mobile_apple_app_store_id', ''),
            'mobile_android_package_name' => config('app.mobile_android_package_name', ''),
            'mobile_android_url' => $downloadUrl,
        ])
            ->additional([
                'status' => 200,
                'message' => 'APK information retrieved successfully.',
            ]);
    }

    /**
     * Download APK file
     */
    public function download()
    {
        $generalSetting = GeneralSetting::first();

        if (! $generalSetting || ! $generalSetting->apk_file_path) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'APK file not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        $filePath = $generalSetting->apk_file_path;

        // New uploads live on the private "local" disk; fall back to "public" for
        // files uploaded before the move so existing installs keep working.
        $disk = Storage::disk('local')->exists($filePath) ? 'local'
            : (Storage::disk('public')->exists($filePath) ? 'public' : null);

        if ($disk === null) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'APK file not found on server.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        return Storage::disk($disk)->download($filePath, 'app-'.$generalSetting->apk_version.'.apk');
    }

    /**
     * Get Tablet APK file information
     */
    public function tabletInfo()
    {
        $generalSetting = GeneralSetting::first();

        if (! $generalSetting || ! $generalSetting->tablet_apk_file_path) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'Tablet APK file not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        // Short-lived signed URL so the APK is not exposed at a permanent public URL.
        $downloadUrl = URL::temporarySignedRoute('api.apk.tablet.download', now()->addMinutes(30));

        return BaseResource::make([
            'version' => $generalSetting->tablet_apk_version,
            'message' => $generalSetting->tablet_apk_release_notes ?? '',
            'mobile_app_id' => config('app.mobile_apple_app_store_id', ''),
            'tablet_android_package_name' => config('app.tablet_android_package_name', ''),
            'tablet_android_url' => $downloadUrl,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Tablet APK information retrieved successfully.',
            ]);
    }

    /**
     * Download Tablet APK file
     */
    public function tabletDownload()
    {
        $generalSetting = GeneralSetting::first();

        if (! $generalSetting || ! $generalSetting->tablet_apk_file_path) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'Tablet APK file not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        $filePath = $generalSetting->tablet_apk_file_path;

        // New uploads live on the private "local" disk; fall back to "public" for
        // files uploaded before the move so existing installs keep working.
        $disk = Storage::disk('local')->exists($filePath) ? 'local'
            : (Storage::disk('public')->exists($filePath) ? 'public' : null);

        if ($disk === null) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'Tablet APK file not found on server.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        return Storage::disk($disk)->download($filePath, 'app-tablet-'.$generalSetting->tablet_apk_version.'.apk');
    }
}
