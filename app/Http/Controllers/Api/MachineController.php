<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\Device;
use App\Models\Machine;
use App\Services\MachineSerialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MachineController extends Controller
{
    public function __construct(
        protected MachineSerialService $serialService
    ) {}

    /**
     * Bind machine to user.
     */
    public function bind(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string'],
            'device_uuid' => ['required', 'string'],
            'serial_number' => ['required', 'string'],
        ]);

        $user = $request->user();

        // 1. Verify Device (tablet) exists
        $device = Device::where('id', $validated['device_id'])
            ->where('uuid', $validated['device_uuid'])
            ->first();

        if (! $device) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => 'Invalid tablet device. Please generate a new QR code from the tablet.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        // 2. Validate serial number format
        if (! $this->serialService->validateFormat($validated['serial_number'])) {
            $formatExample = $this->serialService->getFormatExample();

            return BaseResource::make([])
                ->additional([
                    'status' => 422,
                    'message' => "Invalid serial number format. Expected format: {$formatExample}",
                ])
                ->response()
                ->setStatusCode(422);
        }

        // 3. Check user has active subscription
        $activeSubscription = $user->activeSubscription()->with('subscription')->first();
        if (! $activeSubscription) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'Please subscribe to a plan to bind machines.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // 4. Check machine limit
        $maxMachines = $user->getMaxMachines();
        $currentMachineCount = Machine::where('user_id', $user->id)->count();

        if ($currentMachineCount >= $maxMachines) {
            $planName = $activeSubscription->subscription->title;

            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => "Machine limit reached. Your {$planName} plan allows up to {$maxMachines} machine(s). Please upgrade your subscription to add more machines.",
                ])
                ->response()
                ->setStatusCode(403);
        }

        // 5. Find Machine by serial_number
        $machine = Machine::where('serial_number', $validated['serial_number'])->first();

        if (! $machine) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => "Machine not found with serial number '{$validated['serial_number']}'. Please check the serial number on your machine.",
                ])
                ->response()
                ->setStatusCode(404);
        }

        // 6. Check if machine already bound to another user
        if ($machine->user_id && $machine->user_id !== $user->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This machine is already bound to another user.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // 7. Check machine status
        if (! $machine->isActive()) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This machine is inactive. Please contact support.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Success - bind machine
        $machine->update([
            'user_id' => $user->id,
            'device_id' => $device->id,
            'last_logged_in_at' => now(),
        ]);

        Log::info('Machine bound to user', [
            'user_id' => $user->id,
            'machine_id' => $machine->id,
            'device_id' => $device->id,
            'serial_number' => $machine->serial_number,
        ]);

        return BaseResource::make([
            'machine' => $machine->load(['user', 'device']),
            'subscription' => $activeSubscription->subscription,
            'machines_count' => $currentMachineCount + 1,
            'max_machines' => $maxMachines,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Machine bound successfully.',
            ]);
    }

    /**
     * List user's bound machines.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $machines = Machine::where('user_id', $user->id)
            ->with(['device'])
            ->orderBy('created_at', 'desc')
            ->get();

        return BaseResource::make($machines)
            ->additional([
                'status' => 200,
                'message' => 'Machines retrieved successfully.',
            ]);
    }

    /**
     * Unbind machine from user.
     */
    public function unbind(Request $request, Machine $machine)
    {
        $user = $request->user();

        // Verify machine belongs to current user
        if ($machine->user_id !== $user->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not own this machine.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Unbind machine
        $machine->update([
            'user_id' => null,
            'device_id' => null,
        ]);

        Log::info('Machine unbound from user', [
            'user_id' => $user->id,
            'machine_id' => $machine->id,
        ]);

        return BaseResource::make([])
            ->additional([
                'status' => 200,
                'message' => 'Machine unbound successfully.',
            ]);
    }
}
