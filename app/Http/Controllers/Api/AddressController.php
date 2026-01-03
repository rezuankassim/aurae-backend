<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $customer = $user->customers->first();

        if (! $customer) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => 'Customer not found.',
                ]);
        }

        $addresses = $customer->addresses()->get();

        return AddressResource::collection($addresses)
            ->additional([
                'status' => 200,
                'message' => 'Addresses retrieved successfully.',
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => ['required', 'exists:lunar_countries,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'line_one' => ['required', 'string', 'max:255'],
            'line_two' => ['nullable', 'string', 'max:255'],
            'line_three' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'exists:lunar_states,id'],
            'postcode' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'shipping_default' => ['nullable', 'boolean'],
            'billing_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $customer = $user->customers->first();

        if (! $customer) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'Customer not found.',
                ]);
        }

        // If this address is set as default, unset other defaults
        if ($request->shipping_default) {
            $customer->addresses()->update(['shipping_default' => false]);
        }

        if ($request->billing_default) {
            $customer->addresses()->update(['billing_default' => false]);
        }

        $address = $customer->addresses()->create([
            'country_id' => $request->country_id,
            'title' => $request->title,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'company_name' => $request->company_name,
            'line_one' => $request->line_one,
            'line_two' => $request->line_two,
            'line_three' => $request->line_three,
            'city' => $request->city,
            'state' => $request->state,
            'postcode' => $request->postcode,
            'delivery_instructions' => $request->delivery_instructions,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'shipping_default' => $request->shipping_default ?? false,
            'billing_default' => $request->billing_default ?? false,
        ]);

        return AddressResource::make($address)
            ->additional([
                'status' => 201,
                'message' => 'Address created successfully.',
            ]);
    }

    public function show(Request $request, Address $address)
    {
        $user = $request->user();
        $customer = $user->customers->first();

        if (! $customer || $address->customer_id !== $customer->id) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 403,
                    'message' => 'Unauthorized to access this address.',
                ]);
        }

        return AddressResource::make($address)
            ->additional([
                'status' => 200,
                'message' => 'Address retrieved successfully.',
            ]);
    }

    public function update(Request $request, Address $address)
    {
        $user = $request->user();
        $customer = $user->customers->first();

        if (! $customer || $address->customer_id !== $customer->id) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 403,
                    'message' => 'Unauthorized to update this address.',
                ]);
        }

        $request->validate([
            'country_id' => ['sometimes', 'exists:lunar_countries,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'line_one' => ['sometimes', 'string', 'max:255'],
            'line_two' => ['nullable', 'string', 'max:255'],
            'line_three' => ['nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:255'],
            'shipping_default' => ['nullable', 'boolean'],
            'billing_default' => ['nullable', 'boolean'],
        ]);

        // If this address is set as default, unset other defaults
        if ($request->has('shipping_default') && $request->shipping_default) {
            $customer->addresses()->where('id', '!=', $address->id)->update(['shipping_default' => false]);
        }

        if ($request->has('billing_default') && $request->billing_default) {
            $customer->addresses()->where('id', '!=', $address->id)->update(['billing_default' => false]);
        }

        $address->update($request->only([
            'country_id',
            'title',
            'first_name',
            'last_name',
            'company_name',
            'line_one',
            'line_two',
            'line_three',
            'city',
            'state',
            'postcode',
            'delivery_instructions',
            'contact_email',
            'contact_phone',
            'shipping_default',
            'billing_default',
        ]));

        return AddressResource::make($address->fresh())
            ->additional([
                'status' => 200,
                'message' => 'Address updated successfully.',
            ]);
    }

    public function destroy(Request $request, Address $address)
    {
        $user = $request->user();
        $customer = $user->customers->first();

        if (! $customer || $address->customer_id !== $customer->id) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 403,
                    'message' => 'Unauthorized to delete this address.',
                ]);
        }

        $address->delete();

        return BaseResource::make(null)
            ->additional([
                'status' => 200,
                'message' => 'Address deleted successfully.',
            ]);
    }

    public function countries()
    {
        $priorityCountries = ['Malaysia', 'Singapore', 'Brunei', 'Thailand'];

        $countries = Country::query()->get()->sortBy(function ($country) use ($priorityCountries) {
            $index = array_search($country->name, $priorityCountries);
            return $index !== false ? $index : count($priorityCountries) + array_search($country->name, Country::query()->pluck('name')->toArray());
        })->values();

        return BaseResource::collection($countries)
            ->additional([
                'status' => 200,
                'message' => 'Countries retrieved successfully.',
            ]);
    }

    public function states(Request $request)
    {
        $request->validate([
            'country_id' => ['required', 'exists:lunar_countries,id'],
        ]);

        $states = State::where('country_id', $request->country_id)->get();

        return BaseResource::collection($states)
            ->additional([
                'status' => 200,
                'message' => 'States retrieved successfully.',
            ]);
    }
}
