<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\User;
use App\Models\Verification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Customer;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'password' => 'required',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->device->update([
            'deviceable_type' => User::class,
            'deviceable_id' => $user->id,
        ]);

        $user->token = $user->createToken($request->device->udid)->plainTextToken;

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Login successful.',
            ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'phone_verified_at' => now(),
        ]);

        $customer = Customer::create([
            'first_name' => Str::before($request->name, ' '),
            'last_name' => Str::afterLast($request->name, ' '),
        ]);

        $customer->users()->attach($user->id);

        $request->device->update([
            'deviceable_type' => User::class,
            'deviceable_id' => $user->id,
        ]);

        $user->token = $user->createToken($request->device->udid)->plainTextToken;

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Register successful.',
            ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Logout successful.',
            ]);
    }

    public function sendVerify(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        Verification::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => rand(100000, 999999)]
        );

        // Verify the phone number (e.g., send a verification code) here.
        return BaseResource::make([
            'code' => Verification::where('phone', $request->phone)->first()->code,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Phone verification initiated.',
            ]);
    }

    public function verifyPhone(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $verification = Verification::where('phone', $request->phone)->first();

        if (! $verification || $verification->code !== $request->code) {
            throw ValidationException::withMessages([
                'code' => ['The provided verification code is incorrect.'],
            ]);
        }

        $user = User::where('phone', $request->phone)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['No user found with this phone number.'],
            ]);
        }
        // $user->phone_verified_at = now();
        // $user->save();

        $verification->delete();

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Phone verified successfully.',
            ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['No user found with this phone number.'],
            ]);
        }

        $code = rand(100000, 999999);

        Verification::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $code]
        );

        // Send OTP via Firebase push notification
        $firebaseService = app(FirebaseService::class);
        $firebaseService->sendToUser(
            $user,
            'Password Reset OTP',
            "Your OTP code is: {$code}",
            [
                'type' => 'password_reset',
                'code' => (string) $code,
            ],
            'password_reset'
        );

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'OTP sent to your device successfully.',
            ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'min:6', 'max:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $verification = Verification::where('phone', $request->phone)->first();

        if (! $verification || $verification->code !== $request->code) {
            throw ValidationException::withMessages([
                'code' => ['The provided verification code is incorrect.'],
            ]);
        }

        $user = User::where('phone', $request->phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['No user found with this phone number.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        $verification->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Password reset successfully.',
            ]);
    }
}
