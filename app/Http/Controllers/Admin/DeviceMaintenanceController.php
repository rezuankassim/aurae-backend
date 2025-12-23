<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceMaintenance;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeviceMaintenanceController extends Controller
{
    /**
     * Display a listing of all device maintenance requests.
     */
    public function index(Request $request)
    {
        $query = DeviceMaintenance::with(['user', 'device'])
            ->latest();

        // Apply filters if provided
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('user', function ($userQuery) use ($request) {
                    $userQuery->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%');
                })
                    ->orWhereHas('device', function ($deviceQuery) use ($request) {
                        $deviceQuery->where('name', 'like', '%'.$request->search.'%')
                            ->orWhere('uuid', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $maintenances = $query->paginate(15);

        return Inertia::render('admin/device-maintenances/index', [
            'maintenances' => $maintenances,
            'filters' => [
                'status' => $request->status ?? '',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Display the specified maintenance request.
     */
    public function show(DeviceMaintenance $deviceMaintenance)
    {
        $deviceMaintenance->load(['user', 'device']);

        // Format requested_at_changes for display
        if ($deviceMaintenance->requested_at_changes) {
            $requested_at_changes = collect($deviceMaintenance->requested_at_changes);

            $deviceMaintenance->requested_at_changes_formatted = $requested_at_changes->map(function ($changes) {
                $result = [];
                foreach ($changes as $timestamp => $change) {
                    $result[] = [
                        'changed_at' => $timestamp,
                        'user_id' => $change['user_id'] ?? null,
                        'previous_maintenance_requested_at' => $change['previous_maintenance_requested_at'] ?? null,
                        'new_maintenance_requested_at' => $change['new_maintenance_requested_at'] ?? null,
                        'previous_factory_maintenance_requested_at' => $change['previous_factory_maintenance_requested_at'] ?? null,
                        'new_factory_maintenance_requested_at' => $change['new_factory_maintenance_requested_at'] ?? null,
                    ];
                }

                return $result;
            })->flatten(1);
        } else {
            $deviceMaintenance->requested_at_changes_formatted = [];
        }

        return Inertia::render('admin/device-maintenances/show', [
            'maintenance' => $deviceMaintenance,
        ]);
    }

    /**
     * Update the status of the specified maintenance request.
     */
    public function updateStatus(Request $request, DeviceMaintenance $deviceMaintenance)
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:0,1,2,3'],
            'factory_maintenance_requested_at' => ['nullable', 'date'],
            'is_factory_approved' => ['nullable', 'boolean'],
        ]);

        $updateData = [
            'status' => $validated['status'],
        ];

        if (isset($validated['factory_maintenance_requested_at'])) {
            $updateData['factory_maintenance_requested_at'] = $validated['factory_maintenance_requested_at'];
        }

        if (isset($validated['is_factory_approved'])) {
            $updateData['is_factory_approved'] = $validated['is_factory_approved'];
        }

        // Track changes if factory is proposing a new time
        if (isset($validated['factory_maintenance_requested_at']) &&
            $validated['factory_maintenance_requested_at'] !== $deviceMaintenance->factory_maintenance_requested_at?->toDateTimeString()) {

            $requested_changes = $deviceMaintenance->requested_at_changes ?? [];
            $requested_changes[] = [
                now()->toDateTimeString() => [
                    'user_id' => auth()->id(),
                    'previous_maintenance_requested_at' => $deviceMaintenance->maintenance_requested_at?->toDateTimeString(),
                    'new_maintenance_requested_at' => $deviceMaintenance->maintenance_requested_at?->toDateTimeString(),
                    'previous_factory_maintenance_requested_at' => $deviceMaintenance->factory_maintenance_requested_at?->toDateTimeString(),
                    'new_factory_maintenance_requested_at' => $validated['factory_maintenance_requested_at'],
                ],
            ];
            $updateData['requested_at_changes'] = $requested_changes;
            $updateData['status'] = 0; // Set to pending for user approval
            $updateData['is_user_approved'] = false;
        }

        $deviceMaintenance->update($updateData);

        return to_route('admin.device-maintenances.index')
            ->with('success', 'Device maintenance status updated successfully.');
    }
}
