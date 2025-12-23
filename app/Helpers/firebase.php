<?php

use App\Models\User;
use App\Services\FirebaseService;

if (! function_exists('send_firebase_notification')) {
    /**
     * Send a Firebase notification to a user
     *
     * @param  User|int  $user  User model or user ID
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array  $data  Additional data to send with notification
     * @param  string  $type  Notification type (default: 'general')
     * @return array Results of notification sending
     */
    function send_firebase_notification(User|int $user, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        $firebaseService = app(FirebaseService::class);

        if (is_int($user)) {
            $user = User::find($user);
        }

        if (! $user) {
            return [
                [
                    'success' => false,
                    'error' => 'User not found',
                ],
            ];
        }

        return $firebaseService->sendToUser($user, $title, $body, $data, $type);
    }
}

if (! function_exists('send_firebase_notification_to_token')) {
    /**
     * Send a Firebase notification to a specific FCM token
     *
     * @param  string  $token  FCM device token
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array  $data  Additional data to send with notification
     * @return array Result of notification sending
     */
    function send_firebase_notification_to_token(string $token, string $title, string $body, array $data = []): array
    {
        $firebaseService = app(FirebaseService::class);

        return $firebaseService->sendToDevice($token, $title, $body, $data);
    }
}

if (! function_exists('send_firebase_notification_to_users')) {
    /**
     * Send a Firebase notification to multiple users
     *
     * @param  array  $userIds  Array of user IDs
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array  $data  Additional data to send with notification
     * @param  string  $type  Notification type (default: 'general')
     */
    function send_firebase_notification_to_users(array $userIds, string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        $firebaseService = app(FirebaseService::class);

        return $firebaseService->sendToUsers($userIds, $title, $body, $data, $type);
    }
}

if (! function_exists('send_firebase_notification_to_all')) {
    /**
     * Send a Firebase notification to all users with registered devices
     *
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array  $data  Additional data to send with notification
     * @param  string  $type  Notification type (default: 'general')
     */
    function send_firebase_notification_to_all(string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        $firebaseService = app(FirebaseService::class);

        return $firebaseService->sendToAll($title, $body, $data, $type);
    }
}
