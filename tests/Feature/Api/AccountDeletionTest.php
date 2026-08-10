<?php

use App\Models\Device;
use App\Models\Machine;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSubscription;
use Spatie\Activitylog\Models\Activity;

function apiDeviceHeaders(): array
{
    return [
        'X-Device-Udid' => 'TEST-ACCOUNT-DELETION-DEVICE',
        'X-Device-OS' => 'Android',
        'X-Device-OS-Version' => '13',
        'X-Device-Manufacturer' => 'Samsung',
        'X-Device-Model' => 'Galaxy S21',
        'X-Device-App-Version' => '1.0.0',
    ];
}

test('api account deletion unlinks machines and records self deletion audit', function () {
    $user = User::factory()->create();
    $userDevice = UserDevice::create([
        'udid' => 'TEST-ACCOUNT-DELETION-DEVICE',
        'deviceable_type' => User::class,
        'deviceable_id' => $user->id,
    ]);
    $userSubscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
    ]);
    $device = Device::create([
        'uuid' => 'api-self-delete-device',
        'name' => 'API Self Delete Device',
        'status' => 1,
        'user_id' => $user->id,
    ]);
    $machine = Machine::create([
        'serial_number' => 'AUR20269802',
        'name' => 'API Self Delete Machine',
        'status' => 1,
        'user_id' => $user->id,
        'device_id' => $device->id,
        'user_subscription_id' => $userSubscription->id,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->withHeaders(apiDeviceHeaders())
        ->deleteJson(route('api.account.destroy'), [
            'password' => 'password',
        ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 200,
        'message' => 'Account deleted successfully.',
    ]);

    $machine->refresh();
    $device->refresh();
    $userDevice->refresh();

    expect($user->fresh()->trashed())->toBeTrue();
    expect($machine->user_id)->toBeNull();
    expect($machine->device_id)->toBeNull();
    expect($machine->user_subscription_id)->toBeNull();
    expect($device->user_id)->toBeNull();
    expect($userDevice->deviceable_type)->toBeNull();
    expect($userDevice->deviceable_id)->toBeNull();

    $activity = Activity::query()
        ->where('event', 'user-deleted')
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->latest()
        ->firstOrFail();

    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties->get('deletion_type'))->toBe('self');
});
