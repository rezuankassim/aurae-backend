<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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
