<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeviceMaintenanceStoreRequest;
use App\Http\Resources\BaseResource;
use App\Http\Resources\DeviceMaintenanceResource;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use App\Models\DeviceMaintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceMaintenanceController extends Controller
{
    /**
     * Get the authenticated user's devices.
     */
    public function devices(Request $request)
    {
        $devices = Device::where('user_id', $request->user()->id)
            ->where('status', 1)
            ->get();

        return DeviceResource::collection($devices)
            ->additional([
                'status' => 200,
                'message' => 'Devices retrieved successfully.',
            ]);
    }

    /**
     * Display a listing of maintenance requests for authenticated user.
     */
    public function index(Request $request)
    {
        $maintenances = DeviceMaintenance::with(['device', 'user'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return DeviceMaintenanceResource::collection($maintenances)
            ->additional([
                'status' => 200,
                'message' => 'Device maintenances retrieved successfully.',
            ]);
    }

    /**
     * Store a newly created maintenance request.
     */
    public function store(DeviceMaintenanceStoreRequest $request)
    {
        $validated = $request->validated();

        // Verify device belongs to authenticated user
        $device = Device::findOrFail($validated['device_id']);

        if ($device->user_id !== $request->user()->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not have permission to schedule maintenance for this device.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $maintenance = DeviceMaintenance::create([
            'status' => 1, // pending_factory
            'user_id' => $request->user()->id,
            'device_id' => $validated['device_id'],
            'maintenance_requested_at' => $validated['maintenance_requested_at'],
            'service_type' => $validated['service_type'],
            'is_factory_approved' => false,
            'is_user_approved' => false,
        ]);

        $maintenance->load(['device', 'user']);

        return DeviceMaintenanceResource::make($maintenance)
            ->additional([
                'status' => 201,
                'message' => 'Device maintenance scheduled successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified maintenance request.
     */
    public function show(Request $request, DeviceMaintenance $deviceMaintenance)
    {
        // Verify maintenance belongs to authenticated user
        if ($deviceMaintenance->user_id !== $request->user()->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not have permission to view this maintenance request.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $deviceMaintenance->load(['device', 'user']);

        return DeviceMaintenanceResource::make($deviceMaintenance)
            ->additional([
                'status' => 200,
                'message' => 'Device maintenance retrieved successfully.',
            ]);
    }

    /**
     * Get dates that should be disabled due to scheduled maintenance.
     */
    public function availability(Request $request)
    {
        // Get all dates that have maintenance scheduled
        $disabledDates = DeviceMaintenance::whereNotNull('maintenance_requested_at')
            ->select(DB::raw('DATE(maintenance_requested_at) as date'))
            ->distinct()
            ->pluck('date')
            ->toArray();

        // Also get factory maintenance dates
        $factoryDates = DeviceMaintenance::whereNotNull('factory_maintenance_requested_at')
            ->select(DB::raw('DATE(factory_maintenance_requested_at) as date'))
            ->distinct()
            ->pluck('date')
            ->toArray();

        // Merge and get unique dates
        $allDisabledDates = array_unique(array_merge($disabledDates, $factoryDates));
        sort($allDisabledDates);

        return BaseResource::make(['disabled_dates' => array_values($allDisabledDates)])
            ->additional([
                'status' => 200,
                'message' => 'Maintenance availability retrieved successfully.',
            ]);
    }
}
