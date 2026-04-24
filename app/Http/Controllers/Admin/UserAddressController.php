<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAddressCreateRequest;
use App\Http\Requests\Admin\UserAddressUpdateRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;

class UserAddressController extends Controller
{
    /**
     * Display the user's addresses.
     */
    public function index(User $user)
    {
        $customer = $user->getOrCreateCustomer();

        $addresses = $customer->addresses()
            ->with('country')
            ->orderByDesc('shipping_default')
            ->orderBy('created_at')
            ->get();

        $addresses->map(function ($address) {
            $address->stateData = State::where('code', $address->state)
                ->where('country_id', $address->country_id)
                ->first();

            return $address;
        });

        $countries = Country::with('states')->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'code' => $country->iso3,
                    'states' => $country->states->map(function ($state) {
                        return [
                            'id' => $state->id,
                            'name' => $state->name,
                            'state_code' => $state->code,
                        ];
                    }),
                ];
            });

        return Inertia::render('admin/users/addresses/index', [
            'user' => $user,
            'addresses' => $addresses,
            'countries' => $countries,
        ]);
    }

    /**
     * Store a newly created address for the user.
     */
    public function store(UserAddressCreateRequest $request, User $user)
    {
        $validated = $request->validated();

        $customer = $user->getOrCreateCustomer();

        // If this address is set as default, unset any existing defaults
        if (! empty($validated['is_default'])) {
            $customer->addresses()->update(['shipping_default' => false]);
        }

        if (! empty($validated['is_billing_default'])) {
            $customer->addresses()->update(['billing_default' => false]);
        }

        $customer->addresses()->create([
            'title' => $validated['title'] ?? null,
            'first_name' => Str::before($validated['name'], ' '),
            'last_name' => Str::after($validated['name'], ' '),
            'line_one' => $validated['line1'],
            'line_two' => $validated['line2'] ?? null,
            'line_three' => $validated['line3'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postcode' => $validated['postal_code'] ?? null,
            'country_id' => Country::where('iso3', $validated['country'])->value('id'),
            'delivery_instructions' => $validated['delivery_instructions'] ?? null,
            'contact_email' => $validated['email'] ?? $user->email,
            'contact_phone' => $validated['phone'],
            'shipping_default' => $validated['is_default'] ?? false,
            'billing_default' => $validated['is_billing_default'] ?? false,
        ]);

        return to_route('admin.users.addresses.index', $user->id)
            ->with('success', 'Address created successfully.');
    }

    /**
     * Update the specified address.
     */
    public function update(UserAddressUpdateRequest $request, User $user, Address $address)
    {
        $customer = $user->getOrCreateCustomer();

        abort_unless($address->customer_id === $customer->id, 403);

        $validated = $request->validated();

        // If this address is set as default, unset any existing defaults
        if (! empty($validated['is_default'])) {
            $customer->addresses()
                ->where('id', '!=', $address->id)
                ->update(['shipping_default' => false]);
        }

        if (! empty($validated['is_billing_default'])) {
            $customer->addresses()
                ->where('id', '!=', $address->id)
                ->update(['billing_default' => false]);
        }

        $address->update([
            'title' => $validated['title'] ?? null,
            'first_name' => Str::before($validated['name'], ' '),
            'last_name' => Str::after($validated['name'], ' '),
            'line_one' => $validated['line1'],
            'line_two' => $validated['line2'] ?? null,
            'line_three' => $validated['line3'] ?? null,
            'city' => $validated['city'],
            'state' => $validated['state'] ?? null,
            'postcode' => $validated['postal_code'] ?? null,
            'country_id' => Country::where('iso3', $validated['country'])->value('id'),
            'delivery_instructions' => $validated['delivery_instructions'] ?? null,
            'contact_email' => $validated['email'] ?? $user->email,
            'contact_phone' => $validated['phone'],
            'shipping_default' => $validated['is_default'] ?? false,
            'billing_default' => $validated['is_billing_default'] ?? false,
        ]);

        return to_route('admin.users.addresses.index', $user->id)
            ->with('success', 'Address updated successfully.');
    }

    /**
     * Remove the specified address.
     */
    public function destroy(User $user, Address $address)
    {
        $customer = $user->getOrCreateCustomer();

        abort_unless($address->customer_id === $customer->id, 403);

        $address->delete();

        return to_route('admin.users.addresses.index', $user->id)
            ->with('success', 'Address deleted successfully.');
    }
}
