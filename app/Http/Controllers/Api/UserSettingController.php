<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class UserSettingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $setting = $user->getOrCreateSetting();

        return BaseResource::make($setting)
            ->additional([
                'status' => 200,
                'message' => 'Settings retrieved successfully.',
            ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'allow_app_notification' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $setting = $user->getOrCreateSetting();

        $setting->update($validated);

        return BaseResource::make($setting)
            ->additional([
                'status' => 200,
                'message' => 'Settings updated successfully.',
            ]);
    }
}
