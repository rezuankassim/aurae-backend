<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AddressCreateRequest;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AddressController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Http::get('https://countriesnow.space/api/v0.1/countries/states')->collect(['data'])->map(function ($country) {
            return [
                'name' => $country['name'],
                'code' => $country['iso3'],
                'states' => $country['states'],
            ];
        });

        $addresses = auth()->user()->addresses()->latest()->get();

        $addresses->transform(function ($address) use ($countries) {
            $address->country_label = $countries->firstWhere('code', $address->country) ? $countries->firstWhere('code', $address->country)['name'] : '';

            if ($address->country && $address->state) {
                $country = $countries->firstWhere('code', $address->country);
                if ($country) {
                    $state = collect($country['states'])->firstWhere('state_code', $address->state);
                    $address->state_label = $state ? $state['name'] : '';
                } else {
                    $address->state_label = '';
                }
            } else {
                $address->state_label = '';
            }

            $address->type_label = match ($address->type) {
                0 => 'Home',
                1 => 'Work',
                2 => 'Other',
                default => 'Other',
            };

            return $address;
        });

        return Inertia::render('settings/address', [
            'addresses' => $addresses,
            'countries' => $countries,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddressCreateRequest $request)
    {
        $validated = $request->validated();

        if ($validated['is_default']) {
            // Set all other addresses to not default
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        auth()->user()->addresses()->create($validated);

        return to_route('address.index')->with('success', 'Address saved successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Address $address)
    {
        $countries = Http::get('https://countriesnow.space/api/v0.1/countries/states')->collect(['data'])->map(function ($country) {
            return [
                'name' => $country['name'],
                'code' => $country['iso3'],
                'states' => $country['states'],
            ];
        });

        return Inertia::render('settings/edit-address', [
            'address' => $address,
            'countries' => $countries,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AddressCreateRequest $request, Address $address)
    {
        $validated = $request->validated();

        if ($validated['is_default']) {
            // Set all other addresses to not default
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return to_route('address.index')->with('success', 'Address updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Address $address)
    {
        $address->delete();

        return to_route('address.index')->with('success', 'Address deleted successfully');
    }
}
