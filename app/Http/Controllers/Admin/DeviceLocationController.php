<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceLocationController extends Controller
{
    /**
     * Display a listing of device locations
     */
    public function index(Request $request)
    {
        $query = DeviceLocation::with('userDevice')
            ->latest();

        // Filter by device if specified
        if ($request->has('device_id') && $request->device_id) {
            $query->forDevice($request->device_id);
        }

        // Filter by date range if specified
        if ($request->has('from') || $request->has('to')) {
            $query->dateRange($request->from, $request->to);
        }

        $locations = $query->paginate(50);

        // Get all IoT devices for filter dropdown
        $devices = Device::orderBy('name')
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'label' => sprintf('%s (%s)', $device->name, $device->uuid),
                ];
            });

        return Inertia::render('admin/device-locations/index', [
            'locations' => $locations,
            'devices' => $devices,
            'filters' => [
                'device_id' => $request->device_id,
                'from' => $request->from,
                'to' => $request->to,
            ],
        ]);
    }

    /**
     * Display location history for a specific IoT device
     */
    public function show(Device $device)
    {
        $locations = DeviceLocation::query()
            ->where('device_id', $device->id)
            ->latest()
            ->paginate(50);

        return Inertia::render('admin/device-locations/show', [
            'device' => $device,
            'locations' => $locations,
        ]);
    }
}
