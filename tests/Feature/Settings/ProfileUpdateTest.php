<?php

use App\Models\Device;
use App\Models\Machine;
use App\Models\User;
use App\Models\UserSubscription;
use Spatie\Activitylog\Models\Activity;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();
    $userSubscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
    ]);
    $device = Device::create([
        'uuid' => 'self-delete-device',
        'name' => 'Self Delete Device',
        'status' => 1,
        'user_id' => $user->id,
    ]);
    $machine = Machine::create([
        'serial_number' => 'AUR20269801',
        'name' => 'Self Delete Machine',
        'status' => 1,
        'user_id' => $user->id,
        'device_id' => $device->id,
        'user_subscription_id' => $userSubscription->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh()->trashed())->toBeTrue();

    $machine->refresh();
    $device->refresh();

    expect($machine->user_id)->toBeNull();
    expect($machine->device_id)->toBeNull();
    expect($machine->user_subscription_id)->toBeNull();
    expect($device->user_id)->toBeNull();

    $activity = Activity::query()
        ->where('event', 'user-deleted')
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->latest()
        ->firstOrFail();

    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties->get('deletion_type'))->toBe('self');
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
