<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceAuthenticated;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use tbQuar\Facades\Quar;

class DeviceController extends Controller
{
    public function retrieve(Request $request)
    {
        $request->validate([
            'uuid' => ['required'],
            'name' => ['required'],
        ]);

        // Create or find the device by UUID
        $device = Device::firstOrCreate(
            ['uuid' => $request->uuid],
            [
                'name' => $request->name,
                'status' => 1,
            ]
        );

        // Generate QR code with device credentials for login
        $qr = Quar::format('png')
            ->size(200)
            ->generate(route('api.device.login', ['id' => $device->id, 'uuid' => $device->uuid]));

        return BaseResource::make([
            'qr' => 'data:image/png;base64,'.base64_encode($qr),
            'device_id' => $device->id,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Device retrieved successfully.',
            ]);
    }

    /**
     * Check device ownership status.
     */
    public function check(Request $request)
    {
        $request->validate([
            'device_id' => ['required', 'string'],
            'device_uuid' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Find device by ID and UUID
        $device = Device::where('id', $request->device_id)
            ->where('uuid', $request->device_uuid)
            ->first();

        if (! $device) {
            return BaseResource::make([
                'exists' => false,
                'is_available' => false,
                'belongs_to_user' => false,
                'belongs_to_other' => false,
            ])
                ->additional([
                    'status' => 404,
                    'message' => 'Device not found. Please check the device credentials.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        // Check ownership status
        $isAvailable = is_null($device->user_id);
        $belongsToUser = $device->user_id === $user->id;
        $belongsToOther = ! is_null($device->user_id) && $device->user_id !== $user->id;

        return BaseResource::make([
            'exists' => true,
            'is_available' => $isAvailable,
            'belongs_to_user' => $belongsToUser,
            'belongs_to_other' => $belongsToOther,
            'is_active' => $device->status === 1,
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'uuid' => $device->uuid,
            ],
        ])
            ->additional([
                'status' => 200,
                'message' => $isAvailable
                    ? 'Device is available for binding.'
                    : ($belongsToUser
                        ? 'Device belongs to you.'
                        : 'Device is already linked to another user.'),
            ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'uuid' => ['required'],
        ]);

        $device = Device::where('id', $request->id)
            ->where('uuid', $request->uuid)
            ->firstOrFail();

        // Check if device is not linked to any user, link it to the current user
        if (! $device->user_id) {
            // Check if user has reached device limit based on subscription
            $user = $request->user();
            $maxDevices = $user->getMaxDevices();
            $currentDeviceCount = Device::where('user_id', $user->id)->count();

            if ($currentDeviceCount >= $maxDevices) {
                return BaseResource::make([])
                    ->additional([
                        'status' => 403,
                        'message' => "Device limit reached. Your subscription allows up to {$maxDevices} device(s). Please upgrade your subscription to add more devices.",
                    ])
                    ->response()
                    ->setStatusCode(403);
            }

            $device->user_id = $request->user()->id;
            $device->save();
        }

        // Verify the device belongs to the authenticated user
        if ($device->user_id !== $request->user()->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This device is already linked to another user.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Check if device status is active
        if ($device->status !== 1) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This device is inactive. Please contact support.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Update last logged in timestamp
        $device->update([
            'last_logged_in_at' => now(),
        ]);

        // Pass user token to device
        $token = $request->user()->createToken($device->uuid)->plainTextToken;
        $device->token = $token;

        Log::info('DeviceAuthenticated event created', [
            'device_uuid' => $device->uuid,
            'channel' => 'device.'.$device->uuid,
            'token_length' => strlen($token),
        ]);
        // Broadcast the authentication event with the access token
        DeviceAuthenticated::dispatch($device->uuid, $token);

        return DeviceResource::make($device)
            ->additional([
                'status' => 200,
                'message' => 'Device logged in successfully.',
            ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
