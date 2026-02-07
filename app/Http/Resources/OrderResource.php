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
            'placed_at' => $this->placed_at instanceof \Carbon\Carbon ? $this->placed_at->toIso8601String() : $this->placed_at,
            'created_at' => $this->created_at instanceof \Carbon\Carbon ? $this->created_at->toIso8601String() : $this->created_at,
            'updated_at' => $this->updated_at instanceof \Carbon\Carbon ? $this->updated_at->toIso8601String() : $this->updated_at,
            'lines' => $this->whenLoaded('lines', function () {
                return $this->lines->map(function ($line) {
                    // Handle ShippingOption lines differently (they don't have a real purchasable model)
                    // Use the line type field which is more reliable
                    $isShippingLine = $line->type === 'shipping';

                    // For non-shipping lines, safely get the purchasable (may be null if deleted)
                    $purchasable = null;
                    if (! $isShippingLine) {
                        try {
                            $purchasable = $line->purchasable;
                        } catch (\Throwable $e) {
                            // Purchasable could not be loaded
                            $purchasable = null;
                        }
                    }

                    return [
                        'id' => $line->id,
                        'type' => $line->type ?? ($isShippingLine ? 'shipping' : 'physical'),
                        'quantity' => $line->quantity,
                        'description' => $line->description,
                        'sub_total' => $line->subTotal?->formatted,
                        'discount_total' => $line->discountTotal?->formatted,
                        'total' => $line->total?->formatted,
                        'product' => $isShippingLine ? null : [
                            'id' => $purchasable?->product?->id,
                            'name' => $purchasable?->product?->translateAttribute('name'),
                            'thumbnail' => $purchasable?->product?->thumbnail?->getUrl('medium'),
                        ],
                        'variant' => $isShippingLine ? null : [
                            'id' => $purchasable?->id,
                            'sku' => $purchasable?->sku,
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
