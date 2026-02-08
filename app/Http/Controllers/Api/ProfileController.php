<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\User;
use App\Models\Verification;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Ensure newly added columns are always present in the response payload,
        // even if the user model instance was loaded with a limited select.
        $user->setAttribute('phone_country_code', $user->phone_country_code);

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Profile retrieved successfully.',
            ]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * Fields: name, email, phone, password (optional)
     * If phone is changed, verification is required.
     * If password is provided, it will be updated.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20'],
            'phone_country_code' => ['nullable', 'string', 'max:10'],
        ];

        // Only check phone uniqueness if phone is being changed
        if ($request->phone !== $user->phone) {
            $rules['phone'][] = Rule::unique('users', 'phone');
        }

        // Only validate password fields if password is provided and not empty
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        // Handle password update if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $phoneChanged = $user->phone !== $validated['phone'];

        // Update name, email, and phone_country_code immediately
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_country_code = $validated['phone_country_code'] ?? $user->phone_country_code;

        // If phone is changed, require verification
        if ($phoneChanged) {
            // Store the new phone in verification table for pending verification
            $code = rand(100000, 999999);

            Verification::updateOrCreate(
                ['phone' => $validated['phone']],
                [
                    'code' => $code,
                    'user_id' => $user->id,
                    'verified_at' => null,
                ]
            );

            $user->save();

            // Send OTP via Twilio SMS to the new phone number
            $twilioService = app(TwilioService::class);
            $twilioService->sendOtp($validated['phone'], (string) $code);

            return BaseResource::make([
                'user' => $user,
                'phone_verification_required' => true,
                'new_phone' => $validated['phone'],
            ])
                ->additional([
                    'status' => 200,
                    'message' => 'Profile updated. Please verify your new phone number with the OTP sent to your device.',
                ]);
        }

        $user->save();

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Profile updated successfully.',
            ]);
    }

    /**
     * Verify phone number change with OTP.
     */
    public function verifyPhoneChange(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $user = $request->user();

        $verification = Verification::where('phone', $request->phone)
            ->where('user_id', $user->id)
            ->first();

        if (! $verification || $verification->code !== $request->code) {
            throw ValidationException::withMessages([
                'code' => ['The provided verification code is incorrect.'],
            ]);
        }

        // Check if phone is already taken by another user
        $existingUser = User::where('phone', $request->phone)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is already registered to another account.'],
            ]);
        }

        // Update user's phone
        $user->phone = $request->phone;
        $user->phone_verified_at = now();
        $user->save();

        // Delete verification record
        $verification->delete();

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Phone number verified and updated successfully.',
            ]);
    }

    /**
     * Resend phone verification OTP.
     */
    public function resendPhoneVerificationOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();

        // Check if there's a pending verification for this phone
        $verification = Verification::where('phone', $request->phone)
            ->where('user_id', $user->id)
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'phone' => ['No pending verification found for this phone number.'],
            ]);
        }

        // Generate new OTP
        $code = rand(100000, 999999);
        $verification->update([
            'code' => $code,
            'verified_at' => null,
        ]);

        // Send OTP via Twilio SMS
        $twilioService = app(TwilioService::class);
        $twilioService->sendOtp($request->phone, (string) $code);

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'OTP sent to your phone number successfully.',
            ]);
    }
}
