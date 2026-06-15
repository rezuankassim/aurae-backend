<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserEmergencyContactCreateRequest;
use App\Http\Requests\Admin\UserEmergencyContactUpdateRequest;
use App\Models\EmergencyContact;
use App\Models\User;
use Inertia\Inertia;

class UserEmergencyContactController extends Controller
{
    /**
     * Display the user's emergency contacts.
     */
    public function index(User $user)
    {
        $emergencyContacts = $user->emergencyContacts()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('admin/users/emergency-contacts/index', [
            'user' => $user,
            'emergencyContacts' => $emergencyContacts,
        ]);
    }

    /**
     * Store a newly created emergency contact for the user.
     */
    public function store(UserEmergencyContactCreateRequest $request, User $user)
    {
        $user->emergencyContacts()->create($request->validated());

        return to_route('admin.users.emergency-contacts.index', $user->id)
            ->with('success', 'Emergency contact created successfully.');
    }

    /**
     * Update the specified emergency contact.
     */
    public function update(UserEmergencyContactUpdateRequest $request, User $user, EmergencyContact $emergencyContact)
    {
        abort_unless($emergencyContact->user_id === $user->id, 403);

        $emergencyContact->update($request->validated());

        return to_route('admin.users.emergency-contacts.index', $user->id)
            ->with('success', 'Emergency contact updated successfully.');
    }

    /**
     * Remove the specified emergency contact.
     */
    public function destroy(User $user, EmergencyContact $emergencyContact)
    {
        abort_unless($emergencyContact->user_id === $user->id, 403);

        $emergencyContact->delete();

        return to_route('admin.users.emergency-contacts.index', $user->id)
            ->with('success', 'Emergency contact deleted successfully.');
    }
}
