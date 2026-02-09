<?php

use App\Models\Device;
use App\Models\GeneralSetting;
use App\Models\Machine;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;

beforeEach(function () {
    // Ensure GeneralSetting exists for serial validation
    GeneralSetting::updateOrCreate(
        ['id' => 1],
        [
            'machine_serial_format' => 'AUR-{NNNN}',
            'machine_serial_prefix' => 'AUR',
        ]
    );
});

test('user can bind machine with valid subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(); // max_machines will be set to 1 automatically
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    $device = Device::create([
        'uuid' => 'test-uuid-123',
        'name' => 'Test Tablet',
        'status' => 1,
    ]);

    $machine = Machine::create([
        'serial_number' => 'AUR-0001',
        'name' => 'Test Machine',
        'status' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Device-Udid' => 'test-mobile-device-123'])
        ->postJson('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0001',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 200,
            'message' => 'Machine bound successfully.',
        ]);

    $machine->refresh();
    expect($machine->user_id)->toBe($user->id);
    expect($machine->device_id)->toBe($device->id);
});

test('user cannot bind machine without subscription', function () {
    $user = User::factory()->create();
    $device = Device::create([
        'uuid' => 'test-uuid-123',
        'name' => 'Test Tablet',
        'status' => 1,
    ]);

    $machine = Machine::create([
        'serial_number' => 'AUR-0001',
        'name' => 'Test Machine',
        'status' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Device-Udid' => 'test-mobile-device-123'])
        ->postJson('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0001',
        ]);

    // Should return 403 when no subscription exists
    $response->assertStatus(403);
    expect($response->json('message'))->toContain('subscribe to a plan');
    
    // Verify machine was not bound
    $machine->refresh();
    expect($machine->user_id)->toBeNull();
});

test('user cannot exceed machine limit', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(); // max_machines will be set to 1 automatically
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    $device = Device::create([
        'uuid' => 'test-uuid-123',
        'name' => 'Test Tablet',
        'status' => 1,
    ]);

    Machine::create([
        'serial_number' => 'AUR-0001',
        'name' => 'Machine 1',
        'status' => 1,
        'user_id' => $user->id,
    ]);

    Machine::create([
        'serial_number' => 'AUR-0002',
        'name' => 'Machine 2',
        'status' => 1,
    ]);

    $response = $this->actingAs($user)
        ->withHeaders(['X-Device-Udid' => 'test-mobile-device-123'])
        ->postJson('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0002',
        ]);

    $response->assertStatus(403);
    expect($response->json('message'))->toContain('Machine limit reached');
});

test('user with multiple subscriptions can bind multiple machines', function () {
    $user = User::factory()->create();
    
    // Create 3 subscriptions for the user
    $subscription1 = Subscription::factory()->create();
    $subscription2 = Subscription::factory()->create();
    $subscription3 = Subscription::factory()->create();
    
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription1->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
    
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription2->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
    
    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $subscription3->id,
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);

    $device = Device::create([
        'uuid' => 'test-uuid-123',
        'name' => 'Test Tablet',
        'status' => 1,
    ]);

    // Create 3 machines
    $machine1 = Machine::create([
        'serial_number' => 'AUR-0001',
        'name' => 'Machine 1',
        'status' => 1,
    ]);
    
    $machine2 = Machine::create([
        'serial_number' => 'AUR-0002',
        'name' => 'Machine 2',
        'status' => 1,
    ]);
    
    $machine3 = Machine::create([
        'serial_number' => 'AUR-0003',
        'name' => 'Machine 3',
        'status' => 1,
    ]);

    // Bind first machine
    $response1 = $this->actingAs($user)
        ->withHeaders(['X-Device-Udid' => 'test-mobile-device-123'])
        ->postJson('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0001',
        ]);
    
    $response1->assertStatus(200);

    // Bind second machine
    $response2 = $this->actingAs($user)
        ->withHeaders(['X-Device-Udid' => 'test-mobile-device-123'])
        ->postJson('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0002',
        ]);
    
    $response2->assertStatus(200);

    // Bind third machine
    $response3 = $this->actingAs($user)
        ->withHeaders(['X-Device-Udid' => 'test-mobile-device-123'])
        ->postJson('/api/machine/bind', [
            'device_id' => $device->id,
            'device_uuid' => $device->uuid,
            'serial_number' => 'AUR-0003',
        ]);
    
    $response3->assertStatus(200);

    // Verify all machines are bound
    $machine1->refresh();
    $machine2->refresh();
    $machine3->refresh();
    
    expect($machine1->user_id)->toBe($user->id);
    expect($machine2->user_id)->toBe($user->id);
    expect($machine3->user_id)->toBe($user->id);
    expect($user->machines()->count())->toBe(3);
});
