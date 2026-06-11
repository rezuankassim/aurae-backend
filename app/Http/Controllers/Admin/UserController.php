<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserCreateRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Lunar\Models\Customer;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $showDeleted = $request->boolean('show_deleted');

        $query = User::query()
            ->where('id', '!=', auth()->id())
            ->with('guest');

        if ($showDeleted) {
            $query->withTrashed();
        }

        $users = $query->get();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'show_deleted' => $showDeleted,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/users/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserCreateRequest $request)
    {
        $validated = $request->validated();

        $validated['phone'] = '+60'.$validated['phone'];
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_admin'] = $validated['type'] == 1 ? true : false;

        $user = User::create($validated);

        $customer = Customer::create([
            'first_name' => Str::before($validated['name'], ' '),
            'last_name' => Str::afterLast($validated['name'], ' '),
        ]);

        $customer->users()->attach($user->id);

        return to_route('admin.users.show', $user->id)->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return Inertia::render('admin/users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $validated = $request->validated();

        $user->update($validated);

        $customer = $user->customers()->first();
        if ($customer) {
            $customer->first_name = Str::before($validated['name'], ' ');
            $customer->last_name = Str::afterLast($validated['name'], ' ');
            $customer->save();
        }

        return to_route('admin.users.show', $user->id)->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        $user->delete();
        $params = [];
        if ($request->boolean('show_deleted')) {
            $params['show_deleted'] = 1;
        }

        return to_route('admin.users.index', $params)->with('success', 'User deleted successfully.');
    }

    /**
     * Restore the specified soft-deleted resource.
     */
    public function restore(Request $request, int $user)
    {
        $userRecord = User::withTrashed()->findOrFail($user);

        if ($userRecord->trashed()) {
            $userRecord->restore();
        }

        $params = [];
        if ($request->boolean('show_deleted')) {
            $params['show_deleted'] = 1;
        }

        return to_route('admin.users.index', $params)->with('success', 'User recovered successfully.');
    }
}
