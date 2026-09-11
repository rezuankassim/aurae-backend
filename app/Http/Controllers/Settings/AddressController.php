<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AddressCreateRequest;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;

class AddressController extends Controller
{
    public function index()
    {
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

        $addresses = auth()->user()->customers()->first()?->addresses()->with('country')->get() ?? collect();

        $addresses->map(function ($address) {
            $address->stateData = State::where('id', $address->state)->where('country_id', $address->country_id)->first();

            return $address;
        });

        return Inertia::render('settings/address', [
            'addresses' => $addresses,
            'countries' => $countries,
        ]);
    }

    public function store(AddressCreateRequest $request)
    {
        $validated = $request->validated();

        $customer = auth()->user()->customers()->first();

        $country_id = Country::where('iso3', $validated['country'])->value('id');

        if (! empty($validated['state'])) {
            $state = State::where('code', $validated['state'])->where('country_id', $country_id)->value('id');
        } else {
            $state = null;
        }

        $customer->addresses()->create([
            'title' => $validated['title'] ?? null,
            'first_name' => Str::before($validated['name'], ' '),
            'last_name' => Str::after($validated['name'], ' '),
            'line_one' => $validated['line1'],
            'line_two' => $validated['line2'] ?? null,
            'line_three' => $validated['line3'] ?? null,
            'city' => $validated['city'],
            'state' => $state,
            'postcode' => $validated['postal_code'] ?? null,
            'country_id' => $country_id,
            'delivery_instructions' => $validated['delivery_instructions'] ?? '',
            'contact_email' => $validated['email'] ?? auth()->user()->email,
            'contact_phone' => $validated['phone'],
            'shipping_default' => $validated['is_default'] ?? false,
            'billing_default' => $validated['is_billing_default'] ?? false,
        ]);

        return to_route('address.index')->with('success', 'Address saved successfully');
    }

    public function edit(Address $address)
    {
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

        return Inertia::render('settings/edit-address', [
            'address' => $address->load('country'),
            'countries' => $countries,
        ]);
    }

    public function update(AddressCreateRequest $request, Address $address)
    {
        $validated = $request->validated();

        $country_id = Country::where('iso3', $validated['country'])->value('id');

        if (! empty($validated['state'])) {
            $state = State::where('code', $validated['state'])->where('country_id', $country_id)->value('id');
        } else {
            $state = null;
        }

        $address->update([
            'title' => $validated['title'] ?? null,
            'first_name' => Str::before($validated['name'], ' '),
            'last_name' => Str::after($validated['name'], ' '),
            'line_one' => $validated['line1'],
            'line_two' => $validated['line2'] ?? null,
            'line_three' => $validated['line3'] ?? null,
            'city' => $validated['city'],
            'state' => $state,
            'postcode' => $validated['postal_code'] ?? null,
            'country_id' => $country_id,
            'delivery_instructions' => $validated['delivery_instructions'] ?? '',
            'contact_email' => $validated['email'] ?? auth()->user()->email,
            'contact_phone' => $validated['phone'],
            'shipping_default' => $validated['is_default'] ?? false,
            'billing_default' => $validated['is_billing_default'] ?? false,
        ]);

        return to_route('address.index')->with('success', 'Address updated successfully');
    }

    public function destroy(Address $address)
    {
        $address->delete();

        return to_route('address.index')->with('success', 'Address deleted successfully');
    }
}
