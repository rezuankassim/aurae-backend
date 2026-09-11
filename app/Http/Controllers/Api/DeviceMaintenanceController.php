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

    public function index(Request $request)
    {
        $query = DeviceMaintenance::with(['device', 'user'])
            ->where('user_id', $request->user()->id);

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

    public function store(DeviceMaintenanceStoreRequest $request)
    {
        $validated = $request->validated();

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
            'status' => 1,
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

    public function show(Request $request, DeviceMaintenance $deviceMaintenance)
    {

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

    private const AVAILABLE_TIME_SLOTS = [
        '10:00',
        '11:00',
        '12:00',
        '13:00',
        '15:00',
        '16:00',
        '17:00',
    ];

    public function availability(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $from = $request->input('from');
        $to = $request->input('to');

        $userMaintenanceSlots = DeviceMaintenance::whereNotNull('maintenance_requested_at')
            ->whereDate('maintenance_requested_at', '>=', $from)
            ->whereDate('maintenance_requested_at', '<=', $to)
            ->select(
                DB::raw('DATE(maintenance_requested_at) as date'),
                DB::raw('TIME_FORMAT(maintenance_requested_at, "%H:%i") as time')
            )
            ->get();

        $factoryMaintenanceSlots = DeviceMaintenance::whereNotNull('factory_maintenance_requested_at')
            ->whereDate('factory_maintenance_requested_at', '>=', $from)
            ->whereDate('factory_maintenance_requested_at', '<=', $to)
            ->select(
                DB::raw('DATE(factory_maintenance_requested_at) as date'),
                DB::raw('TIME_FORMAT(factory_maintenance_requested_at, "%H:%i") as time')
            )
            ->get();

        $allMaintenanceSlots = $userMaintenanceSlots->concat($factoryMaintenanceSlots);

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

        $disabledDates = [];
        $disabledTimeSlots = [];

        foreach ($disabledSlotsByDate as $date => $slots) {
            sort($slots);

            if (count(array_intersect($slots, self::AVAILABLE_TIME_SLOTS)) === count(self::AVAILABLE_TIME_SLOTS)) {
                $disabledDates[] = $date;
            }

            $disabledTimeSlots[] = [
                'date' => $date,
                'time_slots' => $slots,
            ];
        }

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

    public function cancel(Request $request, DeviceMaintenance $deviceMaintenance)
    {

        if ($deviceMaintenance->user_id !== $request->user()->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not have permission to cancel this maintenance request.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        if ($deviceMaintenance->is_factory_approved) {
            return BaseResource::make([])
                ->additional([
                    'status' => 422,
                    'message' => 'Cannot cancel maintenance that has already been approved by factory.',
                ])
                ->response()
                ->setStatusCode(422);
        }

        $deviceMaintenance->delete();

        return BaseResource::make([])
            ->additional([
                'status' => 200,
                'message' => 'Maintenance request cancelled successfully.',
            ]);
    }
}
