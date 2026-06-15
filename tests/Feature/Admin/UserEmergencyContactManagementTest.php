<?php

use App\Models\EmergencyContact;
use App\Models\User;

test('admin can create update and delete user emergency contacts', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $user = User::factory()->create();

    $createResponse = $this->actingAs($admin)->post(route('admin.users.emergency-contacts.store', $user), [
        'name' => 'Primary Contact',
        'phone' => '0123456789',
    ]);

    $createResponse->assertRedirect(route('admin.users.emergency-contacts.index', $user));
    $createResponse->assertSessionHas('success', 'Emergency contact created successfully.');

    $contact = EmergencyContact::query()->where('user_id', $user->id)->firstOrFail();

    expect($contact->name)->toBe('Primary Contact');
    expect($contact->phone)->toBe('0123456789');

    $updateResponse = $this->actingAs($admin)->put(route('admin.users.emergency-contacts.update', [
        'user' => $user,
        'emergencyContact' => $contact,
    ]), [
        'name' => 'Updated Contact',
        'phone' => '0987654321',
    ]);

    $updateResponse->assertRedirect(route('admin.users.emergency-contacts.index', $user));
    $updateResponse->assertSessionHas('success', 'Emergency contact updated successfully.');

    $contact->refresh();

    expect($contact->name)->toBe('Updated Contact');
    expect($contact->phone)->toBe('0987654321');

    $deleteResponse = $this->actingAs($admin)->delete(route('admin.users.emergency-contacts.destroy', [
        'user' => $user,
        'emergencyContact' => $contact,
    ]));

    $deleteResponse->assertRedirect(route('admin.users.emergency-contacts.index', $user));
    $deleteResponse->assertSessionHas('success', 'Emergency contact deleted successfully.');

    $this->assertDatabaseMissing('emergency_contacts', [
        'id' => $contact->id,
    ]);
});

test('admin cannot update emergency contact belonging to another user', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherUserContact = EmergencyContact::query()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Contact',
        'phone' => '0111111111',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.users.emergency-contacts.update', [
        'user' => $user,
        'emergencyContact' => $otherUserContact,
    ]), [
        'name' => 'Hacker Update',
        'phone' => '0222222222',
    ]);

    $response->assertForbidden();
});

test('non admin cannot create user emergency contacts', function () {
    $nonAdmin = User::factory()->create([
        'is_admin' => false,
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($nonAdmin)->post(route('admin.users.emergency-contacts.store', $user), [
        'name' => 'Blocked Contact',
        'phone' => '0123000000',
    ]);

    $response->assertForbidden();
});
