<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FirebaseTestController extends Controller
{
    public function __construct(protected FirebaseService $firebaseService) {}

    public function index()
    {
        $users = User::with(['userDevices' => function ($query) {
            $query->whereNotNull('fcm_token');
        }])
            ->whereHas('userDevices', function ($query) {
                $query->whereNotNull('fcm_token');
            })
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'devices_count' => $user->userDevices->count(),
                    'fcm_tokens' => $user->userDevices->pluck('fcm_token')->toArray(),
                ];
            });

        return Inertia::render('admin/FirebaseTest/Index', [
            'users' => $users,
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:token,user,all',
            'fcm_token' => 'required_if:type,token|string',
            'user_id' => 'required_if:type,user|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'data' => 'nullable|array',
        ]);

        $result = match ($validated['type']) {
            'token' => $this->sendToToken($validated),
            'user' => $this->sendToUser($validated),
            'all' => $this->sendToAll($validated),
        };

        return back()->with('firebase_result', $result);
    }

    protected function sendToToken(array $data)
    {
        $result = $this->firebaseService->sendToDevice(
            $data['fcm_token'],
            $data['title'],
            $data['body'],
            $data['data'] ?? []
        );

        return [
            'type' => 'token',
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Notification sent successfully'
                : 'Failed to send notification: '.$result['error'],
            'details' => $result,
        ];
    }

    protected function sendToUser(array $data)
    {
        $user = User::findOrFail($data['user_id']);

        $results = $this->firebaseService->sendToUser(
            $user,
            $data['title'],
            $data['body'],
            $data['data'] ?? [],
            'test'
        );

        $successCount = collect($results)->where('success', true)->count();
        $totalCount = count($results);

        return [
            'type' => 'user',
            'success' => $successCount > 0,
            'message' => "Notification sent to {$successCount}/{$totalCount} devices",
            'details' => [
                'user' => $user->only(['id', 'name', 'email']),
                'results' => $results,
                'success_count' => $successCount,
                'total_count' => $totalCount,
            ],
        ];
    }

    protected function sendToAll(array $data)
    {
        $devicesCount = UserDevice::where('deviceable_type', User::class)
            ->whereNotNull('fcm_token')
            ->distinct('deviceable_id')
            ->count();

        $this->firebaseService->sendToAll(
            $data['title'],
            $data['body'],
            $data['data'] ?? [],
            'test'
        );

        return [
            'type' => 'all',
            'success' => true,
            'message' => "Notification broadcast initiated to {$devicesCount} users",
            'details' => [
                'users_count' => $devicesCount,
            ],
        ];
    }

    public function testToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $isValid = $this->firebaseService->validateToken($validated['fcm_token']);

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid ? 'Token is valid' : 'Token is invalid or expired',
        ]);
    }
}
