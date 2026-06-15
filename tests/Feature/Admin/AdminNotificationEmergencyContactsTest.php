<?php

use App\Models\AdminNotification;
use App\Models\EmergencyContact;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('notification detail includes user emergency contacts', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $user = User::factory()->create();

    EmergencyContact::query()->create([
        'user_id' => $user->id,
        'name' => 'Alice',
        'phone' => '0111111111',
    ]);
    EmergencyContact::query()->create([
        'user_id' => $user->id,
        'name' => 'Bob',
        'phone' => '0222222222',
    ]);

    $notification = AdminNotification::query()->create([
        'type' => 'emergency',
        'title' => 'Emergency Stop: Test Program',
        'body' => 'Emergency stop triggered.',
        'data' => [
            'program_log_id' => 1,
            'therapy_id' => 1,
            'therapy_name' => 'Test Therapy',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_phone' => $user->phone,
            'is_guest' => false,
            'program_duration' => '00:03:20',
            'program_error_message' => null,
            'emergency' => true,
        ],
    ]);

    $response = $this->actingAs($admin)->get(route('admin.notifications.show', $notification));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/notifications/show')
        ->where('notification.id', $notification->id)
        ->where('emergencyContacts', fn ($contacts) => collect($contacts)->pluck('name')->contains('Alice')
            && collect($contacts)->pluck('name')->contains('Bob'))
    );
});

test('notification detail returns empty emergency contacts when user is missing', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $notification = AdminNotification::query()->create([
        'type' => 'emergency',
        'title' => 'Emergency Stop: Missing User',
        'body' => 'Emergency stop triggered.',
        'data' => [
            'user_id' => 999999,
            'user_name' => 'Unknown',
            'user_phone' => 'N/A',
            'is_guest' => false,
            'therapy_name' => 'Test Therapy',
            'program_duration' => '00:01:00',
            'emergency' => true,
        ],
    ]);

    $response = $this->actingAs($admin)->get(route('admin.notifications.show', $notification));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/notifications/show')
        ->where('notification.id', $notification->id)
        ->where('emergencyContacts', [])
    );
});
