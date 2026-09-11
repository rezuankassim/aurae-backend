<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceController extends Controller
{
    public function index()
    {
        $devices = Device::where('user_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('devices/index', [
            'devices' => $devices,
        ]);
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(Device $device)
    {

        if ($device->user_id !== auth()->id()) {
            return to_route('devices.index')->with('error', 'You are not authorized to view this device.');
        }

        return Inertia::render('devices/show', [
            'device' => $device,
        ]);
    }

    public function edit(string $id) {}

    public function update(Request $request, string $id) {}

    public function destroy(string $id) {}
}
