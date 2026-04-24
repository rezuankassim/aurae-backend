<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAddressCreateRequest;
use App\Http\Requests\Admin\UserAddressUpdateRequest;
use App\Models\Address;
use App\Models\User;
use Inertia\Inertia;

class UserAddressController extends Controller
{
    /**
     * Display the user's addresses.
     */
    public function index(User $user)
    {
        $addresses = $user->addresses()->orderByDesc('is_default')->orderBy('created_at')->get();

        return Inertia::render('admin/users/addresses/index', [
            'user' => $user,
            'addresses' => $addresses,
        ]);
    }

    /**
     * Store a newly created address for the user.
     */
    public function store(UserAddressCreateRequest $request, User $user)
    {
        $validated = $request->validated();

        // If this address is set as default, unset any existing defaults
        if (!empty($validated['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->create($validated);

        return to_route('admin.users.addresses.index', $user->id)
            ->with('success', 'Address created successfully.');
    }

    /**
     * Update the specified address.
     */
    public function update(UserAddressUpdateRequest $request, User $user, Address $address)
    {
        $validated = $request->validated();

        // If this address is set as default, unset any existing defaults
        if (!empty($validated['is_default'])) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return to_route('admin.users.addresses.index', $user->id)
            ->with('success', 'Address updated successfully.');
    }

    /**
     * Remove the specified address.
     */
    public function destroy(User $user, Address $address)
    {
        $address->delete();

        return to_route('admin.users.addresses.index', $user->id)
            ->with('success', 'Address deleted successfully.');
    }
}
