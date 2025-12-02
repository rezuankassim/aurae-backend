<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordNotFoundException;
use Illuminate\Http\Request;
use tbQuar\Facades\Quar;

class DeviceController extends Controller
{
    public function retrieve(Request $request)
    {
        $request->validate([
            'uuid' => ['required']
        ]);

        $device = Device::where('uuid', $request->uuid)
            ->firstOrFail();

        $qr = Quar::format('png')
            ->size(200)
            ->generate(route('api.device.login', ['id' => $device->id, 'uuid' => $device->uuid]));

        return BaseResource::make([
                'qr' => 'data:image/png;base64,' . base64_encode($qr)
            ])
            ->additional([
                'status' => 200,
                'message' => 'Device retrieved successfully.'
            ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'id' => ['required'],
            'uuid' => ['required']
        ]);

        $device = Device::where('id', $request->id)
            ->where('uuid', $request->uuid)
            ->where('status', 1)
            ->firstOrFail();

        $device->update([
            'last_logged_in_at' => now(),
        ]);

        // Pass user token to device
        $token = $request->user()->createToken($device->uuid)->plainTextToken;
        $device->token = $token;

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
