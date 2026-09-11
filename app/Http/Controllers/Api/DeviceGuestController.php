<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceAuthenticated;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\GuestResource;
use App\Models\Device;
use App\Models\Guest;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\Models\Customer;

class DeviceGuestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'device_uuid' => ['required', 'exists:devices,uuid'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
        ]);

        $device = Device::where('uuid', $request->device_uuid)
            ->where('status', 1)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $username = 'guest_'.Str::slug($request->phone).'_'.Str::random(6);

            if (User::where('phone', $request->phone)->exists()) {
                $user = User::where('phone', $request->phone)->first();
                $customer = $user->customers()->first();
            } else {
                $user = User::create([
                    'username' => $username,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'email' => $username.'@example.com',
                    'password' => bcrypt(Str::random(32)),
                    'is_admin' => false,
                    'is_guest' => true,
                    'status' => 1,
                ]);

                $customer = Customer::create([
                    'first_name' => $request->name,
                    'last_name' => '',
                ]);

                $customer->users()->attach($user);
            }

            $guest = Guest::create([
                'device_id' => $device->id,
                'name' => $request->name,
                'phone' => $request->phone,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return GuestResource::make($guest->load('user'))
                ->additional([
                    'status' => 201,
                    'message' => 'Guest created successfully.',
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return BaseResource::make([])
                ->additional([
                    'status' => 500,
                    'message' => 'Failed to create guest: '.$e->getMessage(),
                ])
                ->response()
                ->setStatusCode(500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'guest_id' => ['required', 'exists:guests,id'],
            'device_uuid' => ['required', 'exists:devices,uuid'],
        ]);

        $device = Device::where('uuid', $request->device_uuid)->firstOrFail();

        $guest = Guest::where('id', $request->guest_id)
            ->where('device_id', $device->id)
            ->firstOrFail();

        if ($device->status !== 1) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This device is inactive. Please contact support.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        if (! $guest->user_id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'Guest account is not properly configured.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        if (! $guest->user || $guest->user->status !== 1) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'Guest user account not found or is deleted.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $guest->update([
            'last_logged_in_at' => now(),
        ]);

        $device->update([
            'last_logged_in_at' => now(),
        ]);

        $tokenName = "guest-{$guest->id}-device-{$device->uuid}";
        $token = $guest->user->createToken($tokenName)->plainTextToken;
        $guest->token = $token;

        LoginActivity::create([
            'user_id' => $guest->user_id,
            'event' => 'login',
            'guard' => 'api',
            'session_id' => $device->udid,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'succeeded' => true,
            'occurred_at' => now(),
        ]);

        DeviceAuthenticated::dispatch($device->uuid, $token);

        return GuestResource::make($guest->load('user'))
            ->additional([
                'status' => 200,
                'message' => 'Guest logged in successfully.',
            ]);
    }

    public function index(Request $request)
    {
        $request->validate([
            'device_uuid' => ['required', 'exists:devices,uuid'],
        ]);

        $device = Device::where('uuid', $request->device_uuid)->firstOrFail();

        $guests = Guest::where('device_id', $device->id)
            ->whereHas('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return GuestResource::collection($guests)
            ->additional([
                'status' => 200,
                'message' => 'Guests retrieved successfully.',
            ]);
    }

    public function destroy(Request $request, string $guestId)
    {
        $request->validate([
            'device_uuid' => ['required', 'exists:devices,uuid'],
        ]);

        $device = Device::where('uuid', $request->device_uuid)->firstOrFail();

        $guest = Guest::where('id', $guestId)
            ->where('device_id', $device->id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $user = $guest->user;
            $customer = $guest->customer;

            $guest->delete();

            if ($user) {
                $user->tokens()->delete();

                if ($customer) {
                    DB::table('lunar_customer_user')
                        ->where('user_id', $user->id)
                        ->delete();
                }

                $user->delete();
            }

            DB::commit();

            return BaseResource::make([])
                ->additional([
                    'status' => 200,
                    'message' => 'Guest deleted successfully.',
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return BaseResource::make([])
                ->additional([
                    'status' => 500,
                    'message' => 'Failed to delete guest: '.$e->getMessage(),
                ])
                ->response()
                ->setStatusCode(500);
        }
    }
}
