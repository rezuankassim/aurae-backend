<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\Device;
use App\Models\Machine;
use App\Models\UserSubscription;
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
            'subscription_id' => ['nullable', 'integer', 'exists:user_subscriptions,id'],
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

        // 2. Normalize serial number - add space before last digit if not present
        $serialNumber = $validated['serial_number'];
        $serialNumber = trim($serialNumber);

        // If serial is 13 chars without space, convert to format with space (14 chars)
        // Format: A10120260001 1 (model + year + product_code + space + variation)
        if (strlen($serialNumber) === 13 && ! str_contains($serialNumber, ' ')) {
            $serialNumber = substr($serialNumber, 0, 12).' '.substr($serialNumber, 12, 1);
        }

        // Validate serial number format
        if (! $this->serialService->validateFormat($serialNumber)) {
            return BaseResource::make([])
                ->additional([
                    'status' => 422,
                    'message' => 'Invalid serial number format. Expected format: A10120260001 1 (Model + Year + Product Code + Validation)',
                ])
                ->response()
                ->setStatusCode(422);
        }

        // 3. Get the subscription to use
        $selectedSubscription = null;

        if (isset($validated['subscription_id'])) {
            // Use the specified subscription
            $selectedSubscription = UserSubscription::where('id', $validated['subscription_id'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->with('subscription')
                ->first();

            if (! $selectedSubscription) {
                return BaseResource::make([])
                    ->additional([
                        'status' => 404,
                        'message' => 'The specified subscription is not active or does not belong to you.',
                    ])
                    ->response()
                    ->setStatusCode(404);
            }

            // Check if this subscription already has a machine bound
            if ($selectedSubscription->machine()->exists()) {
                return BaseResource::make([])
                    ->additional([
                        'status' => 403,
                        'message' => 'This subscription already has a machine bound to it. Please choose a different subscription.',
                    ])
                    ->response()
                    ->setStatusCode(403);
            }
        } else {
            // Find first active subscription without a machine bound
            $selectedSubscription = $user->subscriptions()
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->whereDoesntHave('machine')
                ->with('subscription')
                ->first();

            if (! $selectedSubscription) {
                // Check if user has any active subscriptions
                $hasActiveSubscriptions = $user->subscriptions()
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                            ->orWhere('ends_at', '>', now());
                    })
                    ->exists();

                if (! $hasActiveSubscriptions) {
                    return BaseResource::make([])
                        ->additional([
                            'status' => 403,
                            'message' => 'Please subscribe to a plan to bind machines.',
                        ])
                        ->response()
                        ->setStatusCode(403);
                }

                return BaseResource::make([])
                    ->additional([
                        'status' => 403,
                        'message' => 'All your subscriptions already have machines bound. Please purchase a new subscription to bind more machines.',
                    ])
                    ->response()
                    ->setStatusCode(403);
            }
        }

        // 4. Check machine limit (total machines vs total active subscriptions)
        $maxMachines = $user->getMaxMachines();
        $currentMachineCount = Machine::where('user_id', $user->id)->count();

        if ($currentMachineCount >= $maxMachines) {
            $planName = $selectedSubscription->subscription->title;

            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => "Machine limit reached. Your {$planName} plan allows up to {$maxMachines} machine(s). Please upgrade your subscription to add more machines.",
                ])
                ->response()
                ->setStatusCode(403);
        }

        // 5. Find Machine by serial_number (try both with and without space)
        $machine = Machine::where('serial_number', $serialNumber)->first();

        // Also try without space in case stored differently
        if (! $machine) {
            $serialWithoutSpace = str_replace(' ', '', $serialNumber);
            $machine = Machine::where('serial_number', $serialWithoutSpace)->first();
        }

        if (! $machine) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => "Machine not found with serial number '{$serialNumber}'. Please check the serial number on your machine.",
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
            'user_subscription_id' => $selectedSubscription->id,
            'last_logged_in_at' => now(),
        ]);

        Log::info('Machine bound to user', [
            'user_id' => $user->id,
            'machine_id' => $machine->id,
            'device_id' => $device->id,
            'user_subscription_id' => $selectedSubscription->id,
            'serial_number' => $machine->serial_number,
        ]);

        return BaseResource::make([
            'machine' => $machine->load(['user', 'device', 'userSubscription.subscription']),
            'subscription' => $selectedSubscription->subscription,
            'user_subscription' => $selectedSubscription,
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
