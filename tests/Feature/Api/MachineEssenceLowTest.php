<?php

use App\Models\Machine;
use App\Models\User;

beforeEach(function () {
    $this->deviceHeaders = [
        'X-Device-Udid' => 'TEST-ESSENCE-LOW-DEVICE',
        'X-Device-OS' => 'Android',
        'X-Device-App-Version' => '1.0.0',
    ];
});

test('owner is notified and a visible record is stored when essence is low', function () {
    $owner = User::factory()->create();
    $machine = Machine::create([
        'serial_number' => 'AUR20260001',
        'name' => 'Living Room Diffuser',
        'status' => 1,
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->postJson("/api/machine/{$machine->id}/essence-low", ['essence_level' => 8]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 200,
            'message' => 'Owner notified of low essence.',
        ])
        ->assertJsonPath('data.type', 'essence_low');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $owner->id,
        'type' => 'essence_low',
        'is_sent' => true,
    ]);
});

test('low essence notification appears in the customer notification history', function () {
    $owner = User::factory()->create();
    $machine = Machine::create([
        'serial_number' => 'AUR20260001',
        'name' => 'Living Room Diffuser',
        'status' => 1,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->postJson("/api/machine/{$machine->id}/essence-low")
        ->assertStatus(200);

    $history = $this->actingAs($owner, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->getJson('/api/notifications');

    $history->assertStatus(200)
        ->assertJsonFragment(['type' => 'essence_low']);
});

test('a user cannot trigger low essence for a machine they do not own', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $machine = Machine::create([
        'serial_number' => 'AUR20260001',
        'name' => 'Living Room Diffuser',
        'status' => 1,
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($stranger, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->postJson("/api/machine/{$machine->id}/essence-low");

    $response->assertStatus(403);
    $this->assertDatabaseCount('notifications', 0);
});

test('triggering low essence for an unknown machine returns 404', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->postJson('/api/machine/non-existent-machine/essence-low');

    $response->assertStatus(404);
});

test('repeated low essence alerts are allowed when the cooldown is disabled', function () {
    $owner = User::factory()->create();
    $machine = Machine::create([
        'serial_number' => 'AUR20260001',
        'name' => 'Living Room Diffuser',
        'status' => 1,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($owner, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->postJson("/api/machine/{$machine->id}/essence-low")
        ->assertStatus(200);

    $this->actingAs($owner, 'sanctum')
        ->withHeaders($this->deviceHeaders)
        ->postJson("/api/machine/{$machine->id}/essence-low")
        ->assertStatus(200);

    $this->assertDatabaseCount('notifications', 2);
});
