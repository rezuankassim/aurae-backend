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
        $query = DeviceMaintenance::with(['device', 'user'])
            ->where('user_id', $request->user()->id);

        // Filter by device_id if provided
        if ($request->has('device_id') && $request->device_id) {
            $query->where('device_id', $request->device_id);
        }

        $maintenances = $query->latest()->get();

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
     * Default available time slots for maintenance.
     */
    private const AVAILABLE_TIME_SLOTS = [
        '10:00',
        '11:00',
        '12:00',
        '13:00',
        '15:00',
        '16:00',
        '17:00',
    ];

    /**
     * Get availability information including disabled dates and time slots.
     */
    public function availability(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        // Get user maintenance slots within date range
        $userMaintenanceSlots = DeviceMaintenance::whereNotNull('maintenance_requested_at')
            ->whereDate('maintenance_requested_at', '>=', $from)
            ->whereDate('maintenance_requested_at', '<=', $to)
            ->select(
                DB::raw('DATE(maintenance_requested_at) as date'),
                DB::raw('TIME_FORMAT(maintenance_requested_at, "%H:%i") as time')
            )
            ->get();

        // Get factory maintenance slots within date range
        $factoryMaintenanceSlots = DeviceMaintenance::whereNotNull('factory_maintenance_requested_at')
            ->whereDate('factory_maintenance_requested_at', '>=', $from)
            ->whereDate('factory_maintenance_requested_at', '<=', $to)
            ->select(
                DB::raw('DATE(factory_maintenance_requested_at) as date'),
                DB::raw('TIME_FORMAT(factory_maintenance_requested_at, "%H:%i") as time')
            )
            ->get();

        // Merge all maintenance slots
        $allMaintenanceSlots = $userMaintenanceSlots->concat($factoryMaintenanceSlots);

        // Group by date and collect disabled time slots
        $disabledSlotsByDate = [];
        foreach ($allMaintenanceSlots as $slot) {
            $date = $slot->date;
            $time = $slot->time;

            if (! isset($disabledSlotsByDate[$date])) {
                $disabledSlotsByDate[$date] = [];
            }

            if (! in_array($time, $disabledSlotsByDate[$date])) {
                $disabledSlotsByDate[$date][] = $time;
            }
        }

        // Sort time slots within each date and identify fully disabled dates
        $disabledDates = [];
        $disabledTimeSlots = [];

        foreach ($disabledSlotsByDate as $date => $slots) {
            sort($slots);

            // Check if all time slots are disabled for this date
            if (count(array_intersect($slots, self::AVAILABLE_TIME_SLOTS)) === count(self::AVAILABLE_TIME_SLOTS)) {
                $disabledDates[] = $date;
            }

            $disabledTimeSlots[] = [
                'date' => $date,
                'time_slots' => $slots,
            ];
        }

        // Sort results by date
        sort($disabledDates);
        usort($disabledTimeSlots, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return BaseResource::make([
            'available_time_slots' => self::AVAILABLE_TIME_SLOTS,
            'disabled_dates' => $disabledDates,
            'disabled_time_slots' => $disabledTimeSlots,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Maintenance availability retrieved successfully.',
            ]);
    }

    /**
     * Cancel a maintenance request if not yet approved by factory.
     */
    public function cancel(Request $request, DeviceMaintenance $deviceMaintenance)
    {
        // Verify maintenance belongs to authenticated user
        if ($deviceMaintenance->user_id !== $request->user()->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not have permission to cancel this maintenance request.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Check if already approved by factory
        if ($deviceMaintenance->is_factory_approved) {
            return BaseResource::make([])
                ->additional([
                    'status' => 422,
                    'message' => 'Cannot cancel maintenance that has already been approved by factory.',
                ])
                ->response()
                ->setStatusCode(422);
        }

        // Delete the maintenance request
        $deviceMaintenance->delete();

        return BaseResource::make([])
            ->additional([
                'status' => 200,
                'message' => 'Maintenance request cancelled successfully.',
            ]);
    }
}
