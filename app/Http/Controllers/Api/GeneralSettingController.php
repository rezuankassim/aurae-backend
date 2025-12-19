<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\GeneralSetting;
use App\Models\SocialMedia;

class GeneralSettingController extends Controller
{
    /**
     * Display the general settings.
     */
    public function index()
    {
        $generalSetting = GeneralSetting::first();
        $socialMedia = SocialMedia::first();

        $data = [
            'general_setting' => $generalSetting,
            'social_media' => $socialMedia,
        ];

        return BaseResource::make($data)
            ->additional([
                'status' => 200,
                'message' => 'General settings and social media retrieved successfully.',
            ]);
    }
}
