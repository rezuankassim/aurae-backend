<x-mail::message>
# New Order Payment Received

A customer has completed payment for an order. Here are the details:

---

## Order Details

**Order Reference:** {{ $order->reference }}
**Date:** {{ $order->placed_at?->format('d M Y, h:i A') }}
**Status:** {{ config('lunar.orders.statuses.'.$order->status.'.label', ucfirst($order->status)) }}

---

## Customer Information

@if ($order->billingAddress)
**Name:** {{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}
@if ($order->billingAddress->company_name)
**Company:** {{ $order->billingAddress->company_name }}
@endif
**Email:** {{ $order->billingAddress->contact_email ?? $order->shippingAddress?->contact_email ?? $order->user?->email ?? 'N/A' }}
@if ($order->billingAddress->contact_phone)
**Phone:** {{ $order->billingAddress->contact_phone }}
@endif
@endif

---

## Items Ordered

@foreach ($order->productLines as $line)
<x-mail::panel>
**{{ $line->description }}**
@if ($line->option)
Option: {{ $line->option }}
@endif
Qty: {{ $line->quantity }} × {{ $line->unit_price->formatted() }}
**Line Total: {{ $line->total->formatted() }}**
</x-mail::panel>
@endforeach

---

## Order Summary

| | |
|---|---|
| Subtotal | {{ $order->sub_total->formatted() }} |
@if ($order->discount_total->value > 0)
| Discount | -{{ $order->discount_total->formatted() }} |
@endif
@if ($order->shipping_total->value > 0)
| Shipping | {{ $order->shipping_total->formatted() }} |
@endif
| Tax | {{ $order->tax_total->formatted() }} |
| **Total** | **{{ $order->total->formatted() }}** |

---

## Shipping Address

@if ($order->shippingAddress)
{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}
{{ $order->shippingAddress->line_one }}
@if ($order->shippingAddress->line_two)
{{ $order->shippingAddress->line_two }}
@endif
{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }} {{ $order->shippingAddress->postcode }}
@endif

---

## Payment Reference

**Reference Number:** {{ $order->meta['senangpay_reference'] ?? $order->reference }}
@if (isset($order->meta['senangpay_transaction_id']))
**Transaction ID:** {{ $order->meta['senangpay_transaction_id'] }}
@endif
**Payment Completed:** {{ isset($order->meta['payment_completed_at']) ? \Carbon\Carbon::parse($order->meta['payment_completed_at'])->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
