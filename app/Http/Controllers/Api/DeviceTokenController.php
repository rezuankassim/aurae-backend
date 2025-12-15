<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Update device FCM token
     */
    public function update(Request $request)
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        // Update the device's FCM token
        $request->device->update([
            'fcm_token' => $request->input('fcm_token'),
        ]);

        return BaseResource::make($request->device)
            ->additional([
                'status' => 200,
                'message' => 'FCM token updated successfully.',
            ]);
    }
}
