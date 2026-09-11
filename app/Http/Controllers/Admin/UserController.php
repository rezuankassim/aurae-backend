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
    public function index(Request $request)
    {
        $showDeleted = $request->boolean('show_deleted');

        $query = User::query()
            ->where('id', '!=', auth()->id())
            ->with('guest');

        if ($showDeleted) {
            $query->withTrashed();
        }

        $users = $query->get()->map(fn (User $user) => $this->userPayload($user));

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => [
                'show_deleted' => $showDeleted,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/create');
    }

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

    public function show(int $user)
    {
        $user = User::withTrashed()->findOrFail($user);

        return Inertia::render('admin/users/show', [
            'user' => $this->userPayload($user),
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/edit', [
            'user' => $user,
        ]);
    }

    protected function userPayload(User $user): array
    {
        return [
            ...$user->toArray(),
            'deletion_audit' => $user->deletionAudit(),
        ];
    }

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

    public function destroy(Request $request, User $user)
    {
        $user->delete();
        $params = [];
        if ($request->boolean('show_deleted')) {
            $params['show_deleted'] = 1;
        }

        return to_route('admin.users.index', $params)->with('success', 'User deleted successfully.');
    }

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
