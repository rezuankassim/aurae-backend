<?php

use App\Models\Device;
use App\Models\Machine;
use App\Models\User;
use App\Models\UserSubscription;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

test('admin users index excludes soft deleted users by default', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $activeUser = User::factory()->create();
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/index')
        ->where('filters.show_deleted', false)
        ->where('users', fn ($users) => collect($users)->pluck('id')->contains($activeUser->id)
            && ! collect($users)->pluck('id')->contains($deletedUser->id))
    );
});

test('admin users index includes soft deleted users when show deleted is enabled', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $activeUser = User::factory()->create();
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['show_deleted' => 1]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/index')
        ->where('filters.show_deleted', true)
        ->where('users', fn ($users) => collect($users)->pluck('id')->contains($activeUser->id)
            && collect($users)->pluck('id')->contains($deletedUser->id))
    );
});

test('admin can recover a deleted user record', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $deletedUser = User::factory()->create();
    $deletedUser->delete();

    $response = $this->actingAs($admin)->put(route('admin.users.restore', $deletedUser->id).'?show_deleted=1');

    $response->assertRedirect(route('admin.users.index', ['show_deleted' => 1]));
    $response->assertSessionHas('success', 'User recovered successfully.');

    $restoredUser = User::withTrashed()->findOrFail($deletedUser->id);
    expect($restoredUser->trashed())->toBeFalse();
});

test('admin deleting a user unlinks their machines and records the admin actor', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $user = User::factory()->create();
    $userSubscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
    ]);
    $device = Device::create([
        'uuid' => 'admin-delete-device',
        'name' => 'Admin Delete Device',
        'status' => 1,
        'user_id' => $user->id,
    ]);
    $machine = Machine::create([
        'serial_number' => 'AUR20269901',
        'name' => 'Admin Delete Machine',
        'status' => 1,
        'user_id' => $user->id,
        'device_id' => $device->id,
        'user_subscription_id' => $userSubscription->id,
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));

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

    expect($activity->causer_id)->toBe($admin->id);
    expect($activity->properties->get('deletion_type'))->toBe('admin');
});

test('admin users index exposes deletion audit data for deleted users', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $deletedUser = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $deletedUser));

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['show_deleted' => 1]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/index')
        ->where('users', function ($users) use ($deletedUser, $admin) {
            $user = collect($users)->firstWhere('id', $deletedUser->id);

            return $user['deletion_audit']['type'] === 'admin'
                && $user['deletion_audit']['actor']['id'] === $admin->id;
        })
    );
});

test('admin can view deletion audit data on a deleted user detail page', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $deletedUser = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $deletedUser));

    $response = $this->actingAs($admin)->get(route('admin.users.show', $deletedUser->id));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/users/show')
        ->where('user.id', $deletedUser->id)
        ->where('user.deletion_audit.type', 'admin')
        ->where('user.deletion_audit.actor.id', $admin->id)
    );
});

test('restoring a deleted user does not relink old machines', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $user = User::factory()->create();
    $userSubscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
    ]);
    $device = Device::create([
        'uuid' => 'restore-device',
        'name' => 'Restore Device',
        'status' => 1,
        'user_id' => $user->id,
    ]);
    $machine = Machine::create([
        'serial_number' => 'AUR20269902',
        'name' => 'Restore Machine',
        'status' => 1,
        'user_id' => $user->id,
        'device_id' => $device->id,
        'user_subscription_id' => $userSubscription->id,
    ]);

    $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response = $this->actingAs($admin)->put(route('admin.users.restore', $user->id));

    $response->assertRedirect(route('admin.users.index'));

    $machine->refresh();
    $device->refresh();

    expect(User::find($user->id))->not->toBeNull();
    expect($machine->user_id)->toBeNull();
    expect($machine->device_id)->toBeNull();
    expect($machine->user_subscription_id)->toBeNull();
    expect($device->user_id)->toBeNull();
});
