<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get shipping total safely to avoid ShippingOption instantiation errors
        $shippingTotal = null;
        try {
            $shippingTotal = $this->shippingTotal?->formatted;
        } catch (\Throwable $e) {
            // Handle cases where shipping_breakdown is malformed
            $shippingTotal = null;
        }

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'sub_total' => $this->subTotal?->formatted,
            'discount_total' => $this->discountTotal?->formatted,
            'shipping_total' => $shippingTotal,
            'tax_total' => $this->taxTotal?->formatted,
            'total' => $this->total?->formatted,
            'currency_code' => $this->currency?->code,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'lines' => $this->whenLoaded('lines', function () {
                return $this->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'quantity' => $line->quantity,
                        'sub_total' => $line->subTotal->formatted,
                        'discount_total' => $line->discountTotal?->formatted,
                        'total' => $line->total->formatted,
                        'product' => [
                            'id' => $line->purchasable?->product?->id,
                            'name' => $line->purchasable?->product?->translateAttribute('name'),
                            'thumbnail' => $line->purchasable?->product?->thumbnail?->getUrl('medium'),
                        ],
                        'variant' => [
                            'id' => $line->purchasable?->id,
                            'sku' => $line->purchasable?->sku,
                        ],
                    ];
                });
            }),
            'shipping_address' => $this->whenLoaded('shippingAddress', function () {
                return [
                    'first_name' => $this->shippingAddress->first_name,
                    'last_name' => $this->shippingAddress->last_name,
                    'line_one' => $this->shippingAddress->line_one,
                    'line_two' => $this->shippingAddress->line_two,
                    'city' => $this->shippingAddress->city,
                    'state' => $this->shippingAddress->state,
                    'postcode' => $this->shippingAddress->postcode,
                    'country' => $this->shippingAddress->country?->name,
                    'contact_email' => $this->shippingAddress->contact_email,
                    'contact_phone' => $this->shippingAddress->contact_phone,
                ];
            }),
            'billing_address' => $this->whenLoaded('billingAddress', function () {
                return [
                    'first_name' => $this->billingAddress->first_name,
                    'last_name' => $this->billingAddress->last_name,
                    'line_one' => $this->billingAddress->line_one,
                    'line_two' => $this->billingAddress->line_two,
                    'city' => $this->billingAddress->city,
                    'state' => $this->billingAddress->state,
                    'postcode' => $this->billingAddress->postcode,
                    'country' => $this->billingAddress->country?->name,
                    'contact_email' => $this->billingAddress->contact_email,
                    'contact_phone' => $this->billingAddress->contact_phone,
                ];
            }),
            'transactions' => $this->whenLoaded('transactions', function () {
                return TransactionResource::collection($this->transactions);
            }),
        ];
    }
}
