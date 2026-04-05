<x-mail::message>
# Thank You for Your Order!

Hi {{ $order->billingAddress?->first_name ?? $order->shippingAddress?->first_name ?? 'Valued Customer' }},

Your payment has been received and your order is confirmed. Below is your invoice summary.

---

## Order Details

**Order Reference:** {{ $order->reference }}
**Date:** {{ $order->placed_at?->format('d M Y, h:i A') }}
**Status:** {{ config('lunar.orders.statuses.'.$order->status.'.label', ucfirst($order->status)) }}

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

@if ($order->billingAddress)
## Billing Address

{{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}
@if ($order->billingAddress->company_name)
{{ $order->billingAddress->company_name }}
@endif
{{ $order->billingAddress->line_one }}
@if ($order->billingAddress->line_two)
{{ $order->billingAddress->line_two }}
@endif
{{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->postcode }}
@endif

---

## Payment Reference

**Reference Number:** {{ $order->meta['senangpay_reference'] ?? $order->reference }}
@if (isset($order->meta['senangpay_transaction_id']))
**Transaction ID:** {{ $order->meta['senangpay_transaction_id'] }}
@endif
**Payment Completed:** {{ isset($order->meta['payment_completed_at']) ? \Carbon\Carbon::parse($order->meta['payment_completed_at'])->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}

---

If you have any questions about your order, please don't hesitate to contact us.

Thank you for shopping with us!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
