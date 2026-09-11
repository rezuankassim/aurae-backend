<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\User;
use App\Models\Verification;
use App\Services\ExabytesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $user->setAttribute('phone_country_code', $user->phone_country_code);

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Profile retrieved successfully.',
            ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20'],
            'phone_country_code' => ['nullable', 'string', 'max:10'],
        ];

        if ($request->phone !== $user->phone) {
            $rules['phone'][] = Rule::unique('users', 'phone')->whereNull('deleted_at');
        }

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $phoneChanged = $user->phone !== $validated['phone'];

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_country_code = $validated['phone_country_code'] ?? $user->phone_country_code;

        $customer = $user->customers()->first();
        if ($customer) {
            $customer->first_name = Str::before($validated['name'], ' ');
            $customer->last_name = Str::after($validated['name'], ' ');
            $customer->save();
        }

        if ($phoneChanged) {

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

            $exabytesService = app(ExabytesService::class);
            $result = $exabytesService->sendOtp($validated['phone'], (string) $code);

            if (! $result['success']) {
                throw ValidationException::withMessages([
                    'phone' => ['Failed to send OTP: '.$result['error']],
                ]);
            }

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

        $existingUser = User::where('phone', $request->phone)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'phone' => ['This phone number is already registered to another account.'],
            ]);
        }

        $user->phone = $request->phone;
        $user->phone_verified_at = now();
        $user->save();

        $verification->delete();

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Phone number verified and updated successfully.',
            ]);
    }

    public function resendPhoneVerificationOtp(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user = $request->user();

        $verification = Verification::where('phone', $request->phone)
            ->where('user_id', $user->id)
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'phone' => ['No pending verification found for this phone number.'],
            ]);
        }

        $code = rand(100000, 999999);
        $verification->update([
            'code' => $code,
            'verified_at' => null,
        ]);

        $exabytesService = app(ExabytesService::class);
        $result = $exabytesService->sendOtp($request->phone, (string) $code);

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'phone' => ['Failed to send OTP: '.$result['error']],
            ]);
        }

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'OTP sent to your phone number successfully.',
            ]);
    }
}
