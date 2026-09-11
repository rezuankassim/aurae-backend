<?php

use App\Models\User;
use App\Services\FirebaseService;

if (! function_exists('send_firebase_notification')) {

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

    function send_firebase_notification_to_token(string $token, string $title, string $body, array $data = []): array
    {
        $firebaseService = app(FirebaseService::class);

        return $firebaseService->sendToDevice($token, $title, $body, $data);
    }
}

if (! function_exists('send_firebase_notification_to_users')) {

    function send_firebase_notification_to_users(array $userIds, string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        $firebaseService = app(FirebaseService::class);

        return $firebaseService->sendToUsers($userIds, $title, $body, $data, $type);
    }
}

if (! function_exists('send_firebase_notification_to_all')) {

    function send_firebase_notification_to_all(string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        $firebaseService = app(FirebaseService::class);

        return $firebaseService->sendToAll($title, $body, $data, $type);
    }
}
