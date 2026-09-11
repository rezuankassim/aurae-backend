<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Mail\Auth\WelcomeMail;
use App\Models\LoginActivity;
use App\Models\User;
use App\Models\Verification;
use App\Services\ExabytesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            LoginActivity::create([
                'user_id' => $user?->id,
                'event' => 'failed',
                'guard' => 'api',
                'session_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'succeeded' => false,
                'occurred_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 1) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'Your user account is inactive. Please contact support.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $request->device->update([
            'deviceable_type' => User::class,
            'deviceable_id' => $user->id,
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'event' => 'login',
            'guard' => 'api',
            'session_id' => $request->device->udid,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'succeeded' => true,
            'occurred_at' => now(),
        ]);

        $user->token = $user->createToken($request->device->udid)->plainTextToken;

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => 'Login successful.',
            ]);
    }

    public function checkUniqueValues(Request $request)
    {

        $existingUser = $request->filled('phone')
            ? User::where('phone', $request->phone)->first()
            : null;
        $uniqueIgnoreId = $existingUser?->isGuest() ? $existingUser->id : null;

        $request->validate([
            'username' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($uniqueIgnoreId)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($uniqueIgnoreId)->whereNull('deleted_at')],
        ], [
            'phone.unique' => 'This phone number is already registered. Please login or use a different phone number.',
            'email.unique' => 'This email address is already registered. Please login or use a different email.',
            'username.unique' => 'This username is already taken. Please choose a different username.',
        ]);

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Values are unique.',
            ]);
    }

    public function register(Request $request)
    {

        $existingUser = User::query()
            ->where('phone', $request->phone)
            ->where('is_guest', true)
            ->first();
        $isOnboarding = $existingUser ? true : false;

        $uniqueIgnoreId = $isOnboarding ? $existingUser->id : null;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($uniqueIgnoreId)->whereNull('deleted_at')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($uniqueIgnoreId)->whereNull('deleted_at')],
        ], [
            'phone.unique' => 'This phone number is already registered. Please login or use a different phone number.',
            'email.unique' => 'This email address is already registered. Please login or use a different email.',
            'username.unique' => 'This username is already taken. Please choose a different username.',
        ]);

        if ($isOnboarding) {
            $user = DB::transaction(function () use ($request, $existingUser) {

                $existingUser->update([
                    'name' => $request->name,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'phone' => $request->phone,
                    'is_guest' => false,
                ]);

                $customer = $existingUser->customers()->first();
                $customerData = [
                    'first_name' => Str::before($request->name, ' '),
                    'last_name' => Str::after($request->name, ' '),
                ];

                if ($customer) {
                    $customer->update($customerData);
                } else {
                    $customer = Customer::create($customerData);
                    $customer->users()->attach($existingUser->id);
                }

                return $existingUser->fresh();
            });
        } else {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
            ]);

            $customer = Customer::create([
                'first_name' => Str::before($request->name, ' '),
                'last_name' => Str::after($request->name, ' '),
            ]);

            $customer->users()->attach($user->id);
        }

        Mail::to($user->email)->queue(new WelcomeMail($user));

        $request->device->update([
            'deviceable_type' => User::class,
            'deviceable_id' => $user->id,
        ]);

        $user->token = $user->createToken($request->device->udid)->plainTextToken;

        return BaseResource::make($user)
            ->additional([
                'status' => 200,
                'message' => $isOnboarding ? 'Account upgraded successfully.' : 'Register successful.',
            ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if ($user->isGuest()) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 403,
                    'message' => 'Guest accounts must be removed via the device guest endpoint.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $request->device->update([
                'deviceable_type' => null,
                'deviceable_id' => null,
            ]);

            $user->userDevices()->delete();

            DB::table('lunar_customer_user')
                ->where('user_id', $user->id)
                ->delete();

            LoginActivity::create([
                'user_id' => $user->id,
                'event' => 'logout',
                'guard' => 'api',
                'session_id' => $request->device->udid,
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                'succeeded' => true,
                'occurred_at' => now(),
                'logout_at' => now(),
            ]);

            $user->tokens()->delete();

            $user->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return BaseResource::make(null)
                ->additional([
                    'status' => 500,
                    'message' => 'Failed to delete account: '.$e->getMessage(),
                ])
                ->response()
                ->setStatusCode(500);
        }

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Account deleted successfully.',
            ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $tokenName = $user->currentAccessToken()->name;

        $updated = LoginActivity::where('user_id', $user->id)
            ->where('event', 'login')
            ->where('guard', 'api')
            ->where('session_id', $tokenName)
            ->whereNull('logout_at')
            ->latest('occurred_at')
            ->limit(1)
            ->update(['logout_at' => now()]);

        if (! $updated) {
            LoginActivity::where('user_id', $user->id)
                ->where('event', 'login')
                ->where('guard', 'api')
                ->whereNull('logout_at')
                ->latest('occurred_at')
                ->limit(1)
                ->update(['logout_at' => now()]);
        }

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

        $code = rand(100000, 999999);

        Verification::updateOrCreate(
            ['phone' => $request->phone],
            ['code' => $code]
        );

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
        if ($user) {
            $user->phone_verified_at = now();
            $user->save();
        }

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

    public function verifyResetOtp(Request $request)
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

        $verification->update(['verified_at' => now()]);

        return BaseResource::make([
            'phone' => $request->phone,
        ])
            ->additional([
                'status' => 200,
                'message' => 'OTP verified successfully. You can now reset your password.',
            ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $verification = Verification::where('phone', $request->phone)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(5))
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'phone' => ['OTP verification expired or not completed. Please request a new OTP.'],
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

        $user->tokens()->delete();

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Password reset successfully. Please login with your new password.',
            ]);
    }
}
