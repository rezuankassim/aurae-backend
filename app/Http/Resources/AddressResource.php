<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AddressResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'country_id' => $this->country_id,
            'title' => $this->title,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company_name' => $this->company_name,
            'line_one' => $this->line_one,
            'line_two' => $this->line_two,
            'line_three' => $this->line_three,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'delivery_instructions' => $this->delivery_instructions,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'shipping_default' => (bool) $this->shipping_default,
            'billing_default' => (bool) $this->billing_default,
            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                    'iso3' => $this->country->iso3,
                    'iso2' => $this->country->iso2,
                    'phonecode' => $this->country->phonecode,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
