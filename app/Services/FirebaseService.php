<?php

namespace App\Services;

use App\Models\Notification as NotificationModel;
use App\Models\User;
use App\Models\UserDevice;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials.file');
        $credentialsPath = $credentialsPath ? base_path($credentialsPath) : null;

        if ($credentialsPath && file_exists($credentialsPath)) {
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        }
    }

    /**
     * Send notification to a single user
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], string $type = 'general')
    {
        // Check if user has disabled app notifications
        $setting = $user->setting;
        if ($setting && ! $setting->allow_app_notification) {
            // Store notification record but don't send
            NotificationModel::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'type' => $type,
                'is_sent' => false,
                'sent_at' => now(),
                'error_message' => 'User has disabled app notifications',
            ]);

            return [['success' => false, 'error' => 'User has disabled app notifications']];
        }

        $devices = UserDevice::where('deviceable_type', User::class)
            ->where('deviceable_id', $user->id)
            ->whereNotNull('fcm_token')
            ->get();

        $results = [];
        foreach ($devices as $device) {
            $result = $this->sendToDevice($device->fcm_token, $title, $body, $data);
            $results[] = $result;
        }

        // Store notification record
        NotificationModel::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'type' => $type,
            'is_sent' => collect($results)->contains('success', true),
            'sent_at' => now(),
            'error_message' => collect($results)->where('success', false)->pluck('error')->filter()->implode(', ') ?: null,
        ]);

        return $results;
    }

    /**
     * Send a push notification to a user's devices WITHOUT creating a notification record.
     *
     * The caller is responsible for persisting the Notification record. Respects the
     * user's allow_app_notification setting (returns a skipped result without sending).
     *
     * @return array{sent: bool, skipped: bool, error: string|null, results: array}
     */
    public function pushToUser(User $user, string $title, string $body, array $data = []): array
    {
        // Respect the user's notification preference.
        $setting = $user->setting;
        if ($setting && ! $setting->allow_app_notification) {
            return [
                'sent' => false,
                'skipped' => true,
                'error' => 'User has disabled app notifications',
                'results' => [],
            ];
        }

        $devices = UserDevice::where('deviceable_type', User::class)
            ->where('deviceable_id', $user->id)
            ->whereNotNull('fcm_token')
            ->get();

        $results = [];
        foreach ($devices as $device) {
            $results[] = $this->sendToDevice($device->fcm_token, $title, $body, $data);
        }

        return [
            'sent' => collect($results)->contains('success', true),
            'skipped' => false,
            'error' => collect($results)->where('success', false)->pluck('error')->filter()->implode(', ') ?: null,
            'results' => $results,
        ];
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = [], string $type = 'general')
    {
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data, $type);
        }

        return true;
    }

    /**
     * Send notification to all users
     */
    public function sendToAll(string $title, string $body, array $data = [], string $type = 'general')
    {
        $devices = UserDevice::where('deviceable_type', User::class)
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($devices->unique('deviceable_id') as $device) {
            if ($device->deviceable) {
                $this->sendToUser($device->deviceable, $title, $body, $data, $type);
            }
        }

        return true;
    }

    /**
     * Send notification to a specific device token
     */
    public function sendToDevice(string $token, string $title, string $body, array $data = [])
    {
        if (! $this->messaging) {
            return [
                'success' => false,
                'error' => 'Firebase messaging not initialized. Please check your credentials.',
            ];
        }

        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data)
                ->toToken($token);

            $this->messaging->send($message);

            return [
                'success' => true,
                'token' => $token,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'token' => $token,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate FCM token
     */
    public function validateToken(string $token)
    {
        if (! $this->messaging) {
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create('Test', 'Test'));

            $this->messaging->validate($message);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
