<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\NotificationResource;
use App\Models\Device;
use App\Models\Machine;
use App\Models\Notification;
use App\Models\UserSubscription;
use App\Services\FirebaseService;
use App\Services\MachineSerialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MachineController extends Controller
{
    private const ESSENCE_LOW_TYPE = 'essence_low';

    private const ESSENCE_LOW_COOLDOWN_HOURS = 0;

    public function __construct(
        protected MachineSerialService $serialService,
        protected FirebaseService $firebaseService
    ) {}

    public function bind(Request $request)
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string'],
            'device_uuid' => ['required', 'string'],
            'serial_number' => ['required', 'string'],
            'subscription_id' => ['nullable', 'integer', 'exists:user_subscriptions,id'],
        ]);

        $user = $request->user();

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

        $serialNumber = trim($validated['serial_number']);

        if (! $this->serialService->validateFormat($serialNumber)) {
            return BaseResource::make([])
                ->additional([
                    'status' => 422,
                    'message' => 'Invalid serial number format. Expected format: ABC20260001 or A10120260001 (Model + Year + Product Code)',
                ])
                ->response()
                ->setStatusCode(422);
        }

        $selectedSubscription = null;

        if (isset($validated['subscription_id'])) {

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

        $machine = Machine::where('serial_number', $serialNumber)->first();

        if (! $machine) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => "Machine not found with serial number '{$serialNumber}'. Please check the serial number on your machine.",
                ])
                ->response()
                ->setStatusCode(404);
        }

        if ($machine->user_id && $machine->user_id !== $user->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This machine is already bound to another user.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        if (! $machine->isActive()) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This machine is inactive. Please contact support.',
                ])
                ->response()
                ->setStatusCode(403);
        }

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

    public function unbind(Request $request, Machine $machine)
    {
        $user = $request->user();

        if ($machine->user_id !== $user->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not own this machine.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        if ($machine->device) {
            $machine->device->update(['user_id' => null]);
        }

        $machine->update([
            'user_id' => null,
            'device_id' => null,
            'user_subscription_id' => null,
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

    public function changeSubscription(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:user_subscriptions,id'],
        ]);

        $user = $request->user();

        if ($machine->user_id !== $user->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not own this machine.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $targetSubscription = UserSubscription::where('id', $validated['subscription_id'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->with('subscription')
            ->first();

        if (! $targetSubscription) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => 'The specified subscription is not active or does not belong to you.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        if ($machine->user_subscription_id === $targetSubscription->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 400,
                    'message' => 'This machine is already bound to this subscription.',
                ])
                ->response()
                ->setStatusCode(400);
        }

        if ($targetSubscription->machine()->exists()) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'The target subscription already has a machine bound to it. Please choose a different subscription.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $previousSubscriptionId = $machine->user_subscription_id;

        $machine->update([
            'user_subscription_id' => $targetSubscription->id,
        ]);

        Log::info('Machine subscription changed', [
            'user_id' => $user->id,
            'machine_id' => $machine->id,
            'previous_subscription_id' => $previousSubscriptionId,
            'new_subscription_id' => $targetSubscription->id,
        ]);

        return BaseResource::make([
            'machine' => $machine->load(['user', 'device', 'userSubscription.subscription']),
            'subscription' => $targetSubscription->subscription,
            'user_subscription' => $targetSubscription,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Machine subscription changed successfully.',
            ]);
    }

    public function essenceLow(Request $request, string $machine)
    {
        $validated = $request->validate([
            'essence_level' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        $machine = Machine::find($machine);

        if (! $machine) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => 'Machine not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        if ($machine->user_id !== $user->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not own this machine.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        if (self::ESSENCE_LOW_COOLDOWN_HOURS > 0) {
            $recent = Notification::where('user_id', $user->id)
                ->where('type', self::ESSENCE_LOW_TYPE)
                ->where('created_at', '>=', now()->subHours(self::ESSENCE_LOW_COOLDOWN_HOURS))
                ->orderByDesc('created_at')
                ->get()
                ->first(fn (Notification $notification) => ($notification->data['machine_id'] ?? null) === $machine->id);

            if ($recent) {
                return NotificationResource::make($recent)
                    ->additional([
                        'status' => 200,
                        'message' => 'Owner was recently notified of low essence.',
                        'throttled' => true,
                    ]);
            }
        }

        $machineName = $machine->name ?: $machine->serial_number;
        $title = 'Low essential oil';
        $body = isset($validated['essence_level'])
            ? "Your machine \"{$machineName}\" is low on essential oil ({$validated['essence_level']}% remaining). Please refill soon."
            : "Your machine \"{$machineName}\" is low on essential oil. Please refill soon.";

        $data = [
            'type' => self::ESSENCE_LOW_TYPE,
            'machine_id' => $machine->id,
            'serial_number' => $machine->serial_number,
        ];

        if (isset($validated['essence_level'])) {
            $data['essence_level'] = $validated['essence_level'];
        }

        $push = $this->firebaseService->pushToUser($user, $title, $body, $data);

        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'type' => self::ESSENCE_LOW_TYPE,
            'is_sent' => true,
            'sent_at' => now(),
            'error_message' => $push['sent'] ? null : $push['error'],
        ]);

        Log::info('Low essence notification triggered', [
            'user_id' => $user->id,
            'machine_id' => $machine->id,
            'serial_number' => $machine->serial_number,
            'push_sent' => $push['sent'],
            'push_skipped' => $push['skipped'],
        ]);

        return NotificationResource::make($notification)
            ->additional([
                'status' => 200,
                'message' => 'Owner notified of low essence.',
                'push_sent' => $push['sent'],
            ])
            ->response()
            ->setStatusCode(200);
    }
}
