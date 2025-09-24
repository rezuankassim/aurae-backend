<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceMaintenanceCreateRequest;
use App\Http\Requests\DeviceMaintenanceUpdateRequest;
use App\Models\DeviceMaintenance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceMaintenanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $deviceMaintenances = DeviceMaintenance::where('user_id', auth()->id())->latest()->get();

        return Inertia::render('device-maintenances/index', [
            'deviceMaintenances' => $deviceMaintenances,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('device-maintenances/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeviceMaintenanceCreateRequest $request)
    {
        $validated = $request->validated();

        DeviceMaintenance::create([
            'status' => 1, // pending_factory
            'maintenance_requested_at' => Carbon::parse($validated['maintenance_date'] . ' ' . $validated['maintenance_time']),
            'user_id' => auth()->id(),
        ]);

        return to_route('device-maintenance.index')->with('success', 'Device maintenance scheduled successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DeviceMaintenance $deviceMaintenance)
    {
        if ($deviceMaintenance->user_id !== auth()->id() || !auth()->user()->is_admin) {
            return to_route('device-maintenance.index')->with('error', 'You are not authorized to view this maintenance request.');
        }

        transform($deviceMaintenance, function ($item) {
            if ($item->requested_at_changes) {
                $requested_at_changes = collect(json_decode($item->requested_at_changes)[0]);

                $requested_at_changes_formatted = $requested_at_changes->map(function ($value, $key) {
                    return (object) [
                        'changed_at' => $key,
                        'user' => User::find($value->user_id),
                        'previous_maintenance_requested_at' => $value->previous_maintenance_requested_at,
                        'new_maintenance_requested_at' => $value->new_maintenance_requested_at,
                        'previous_factory_maintenance_requested_at' => $value->previous_factory_maintenance_requested_at,
                        'new_factory_maintenance_requested_at' => $value->new_factory_maintenance_requested_at
                    ];
                })->values();

                $item->requested_at_changes_formatted = $requested_at_changes_formatted;
            } else {
                $item->requested_at_changes_formatted = [];
            }

            return $item;
        });

        return Inertia::render('device-maintenances/show', [
            'deviceMaintenance' => $deviceMaintenance,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeviceMaintenance $deviceMaintenance)
    {
        if ($deviceMaintenance->user_id !== auth()->id()) {
            return to_route('device-maintenance.index')->with('error', 'You are not authorized to edit this maintenance request.');
        }

        return Inertia::render('device-maintenances/edit', [
            'deviceMaintenance' => $deviceMaintenance,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DeviceMaintenanceUpdateRequest $request, DeviceMaintenance $deviceMaintenance)
    {
        if ($deviceMaintenance->user_id !== auth()->id()) {
            return to_route('device-maintenance.index')->with('error', 'You are not authorized to update this maintenance request.');
        }

        $validated = $request->validated();

        $requested_changes = $deviceMaintenance->requested_at_changes ? json_decode($deviceMaintenance->requested_at_changes, true) : [];
        $requested_changes[] = [
            now()->toDateTimeString() => [
                'user_id' => auth()->id(),
                'previous_maintenance_requested_at' => $deviceMaintenance->maintenance_requested_at->toDateTimeString(),
                'new_maintenance_requested_at' => Carbon::parse($validated['maintenance_date'] . ' ' . $validated['maintenance_time'])->toDateTimeString(),
                'previous_factory_maintenance_requested_at' => $deviceMaintenance->factory_maintenance_requested_at ? $deviceMaintenance->factory_maintenance_requested_at->toDateTimeString() : null,
                'new_factory_maintenance_requested_at' => null,
            ]
        ];

        $deviceMaintenance->update([
            'maintenance_requested_at' => Carbon::parse($validated['maintenance_date'] . ' ' . $validated['maintenance_time']),
            'factory_maintenance_requested_at' => null,
            'status' => 1, // pending_factory
            'is_user_approved' => false,
            'is_factory_approved' => false,
            'requested_at_changes' => json_encode($requested_changes),
        ]);

        return to_route('device-maintenance.index')->with('success', 'Device maintenance updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeviceMaintenance $deviceMaintenance)
    {
        // if ($deviceMaintenance->user_id !== auth()->id()) {
        //     return to_route('device-maintenance.index')->with('error', 'You are not authorized to delete this maintenance request.');
        // }

        // if ($deviceMaintenance->status === 1 && !$deviceMaintenance->is_factory_approved && $deviceMaintenance->requested_at_changes === null) {
        //     return to_route('device-maintenance.index')->with('error', 'Only maintenance requests that havent been viewed yet can be deleted.');
        // }

        // $deviceMaintenance->delete();

        // return to_route('device-maintenance.index')->with('success', 'Device maintenance request deleted successfully.');
    }

    /**
     * Client approves the maintenance request.
     */
    public function approve(DeviceMaintenance $deviceMaintenance)
    {   
        abort_if($deviceMaintenance->user_id !== auth()->id(), 403);
        abort_if($deviceMaintenance->status !== 0, 400, 'This maintenance request is not pending approval.');
        abort_if($deviceMaintenance->is_user_approved, 400, 'You have already approved this maintenance request.');
        abort_if($deviceMaintenance->is_factory_approved, 400, 'Factory has not approved this maintenance request yet.');

        $deviceMaintenance->update([
            'is_user_approved' => true,
            'status' => 2, // in_progress
        ]);

        return to_route('device-maintenance.index')->with('success', 'You have approved the maintenance request.');
    }
}
