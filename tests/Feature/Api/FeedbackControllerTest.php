<?php

use App\Models\User;

function deviceHeaders(): array
{
    return [
        'X-Device-Udid' => 'TEST-DEVICE-UDID-FEEDBACK',
        'X-Device-OS' => 'Android',
        'X-Device-OS-Version' => '13',
        'X-Device-Manufacturer' => 'Samsung',
        'X-Device-Model' => 'Galaxy S21',
        'X-Device-App-Version' => '1.0.0',
    ];
}

test('guest can submit feedback without authentication', function () {
    $description = 'Guest feedback message';

    $response = $this->withHeaders(deviceHeaders())
        ->postJson('/api/feedback', [
            'description' => $description,
        ]);

    $response->assertStatus(201);
    $response->assertJson([
        'status' => 200,
        'message' => 'Feedback submitted successfully.',
    ]);

    $this->assertDatabaseHas('feedback', [
        'description' => $description,
        'user_id' => null,
    ]);
});

test('authenticated user feedback stores user id', function () {
    $user = User::factory()->create();
    $description = 'Authenticated feedback message';

    $response = $this->actingAs($user, 'sanctum')
        ->withHeaders(deviceHeaders())
        ->postJson('/api/feedback', [
            'description' => $description,
        ]);

    $response->assertStatus(201);
    $response->assertJson([
        'status' => 200,
        'message' => 'Feedback submitted successfully.',
    ]);

    $this->assertDatabaseHas('feedback', [
        'description' => $description,
        'user_id' => $user->id,
    ]);
});
