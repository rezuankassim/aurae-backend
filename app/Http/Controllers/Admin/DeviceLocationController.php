<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceLocation;
use App\Models\UserDevice;
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

        // Get all devices for filter dropdown
        $devices = UserDevice::orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($device) {
                return [
                    'id' => $device->id,
                    'label' => sprintf('%s %s (%s)', $device->manufacturer ?? 'Unknown', $device->model ?? 'Device', $device->udid),
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
     * Display location history for a specific device
     */
    public function show(UserDevice $userDevice)
    {
        $locations = DeviceLocation::query()
            ->where('user_device_id', $userDevice->id)
            ->latest()
            ->paginate(50);

        return Inertia::render('admin/device-locations/show', [
            'device' => $userDevice,
            'locations' => $locations,
        ]);
    }
}
