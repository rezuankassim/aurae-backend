<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceAuthenticated;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\GuestResource;
use App\Models\Device;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lunar\Models\Customer;

class DeviceGuestController extends Controller
{
    /**
     * Create a new guest for a device.
     */
    public function store(Request $request)
    {
        $request->validate([
            'device_uuid' => ['required', 'exists:devices,uuid'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
        ]);

        // Verify device exists and is active
        $device = Device::where('uuid', $request->device_uuid)
            ->where('status', 1)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Create a user account for the guest
            // Generate a unique username based on phone and random string
            $username = 'guest_'.Str::slug($request->phone).'_'.Str::random(6);

            $user = User::create([
                'username' => $username,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $username.'@example.com', // Guests don't need email
                'password' => bcrypt(Str::random(32)), // Random password, won't be used
                'is_admin' => false,
                'status' => 1,
            ]);

            // Create Lunar customer profile for the guest
            $customer = Customer::create([
                'first_name' => $request->name,
                'last_name' => '',
            ]);

            // Link customer to user
            $customer->users()->attach($user);

            // Create guest record
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

    /**
     * Guest login (passwordless).
     */
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

        // Check if device status is active
        if ($device->status !== 1) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'This device is inactive. Please contact support.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Check if guest has a linked user account
        if (! $guest->user_id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'Guest account is not properly configured.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        // Update last logged in timestamp for both guest and device
        $guest->update([
            'last_logged_in_at' => now(),
        ]);

        $device->update([
            'last_logged_in_at' => now(),
        ]);

        // Generate token for the guest's user account
        $tokenName = "guest-{$guest->id}-device-{$device->uuid}";
        $token = $guest->user->createToken($tokenName)->plainTextToken;
        $guest->token = $token;

        // Broadcast the authentication event
        DeviceAuthenticated::dispatch($device->uuid, $token);

        return GuestResource::make($guest->load('user'))
            ->additional([
                'status' => 200,
                'message' => 'Guest logged in successfully.',
            ]);
    }

    /**
     * List all guests for a device.
     */
    public function index(Request $request)
    {
        $request->validate([
            'device_uuid' => ['required', 'exists:devices,uuid'],
        ]);

        $device = Device::where('uuid', $request->device_uuid)->firstOrFail();

        $guests = Guest::where('device_id', $device->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return GuestResource::collection($guests)
            ->additional([
                'status' => 200,
                'message' => 'Guests retrieved successfully.',
            ]);
    }

    /**
     * Delete a guest from a device.
     */
    public function destroy(Request $request, string $guestId)
    {
        $request->validate([
            'device_uuid' => ['required', 'exists:devices,uuid'],
        ]);

        $device = Device::where('uuid', $request->device_uuid)->firstOrFail();

        // Find guest and ensure it belongs to the specified device
        $guest = Guest::where('id', $guestId)
            ->where('device_id', $device->id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Get the user and customer before deleting guest
            $user = $guest->user;
            $customer = $guest->customer;

            // Delete the guest record
            $guest->delete();

            // Revoke all tokens for the guest user
            if ($user) {
                $user->tokens()->delete();

                // Delete the user account
                $user->delete();
            }

            // Optionally delete the Lunar customer record
            // Note: You might want to keep customer records for order history
            // Uncomment the line below if you want to delete customers as well
            // $customer?->delete();

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
