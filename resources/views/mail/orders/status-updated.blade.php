<x-mail::message>
# Order Status Update

Hi {{ $order->billingAddress?->first_name ?? $order->shippingAddress?->first_name ?? 'Valued Customer' }},

We have an update on your order. Here are the latest details:

<x-mail::panel>
**Order Reference:** {{ $order->reference }}
**Updated Status:** {{ config('lunar.orders.statuses.'.$order->status.'.label', ucfirst($order->status)) }}
@if (isset($order->meta['tracking_link']))
**Tracking Link:** {{ $order->meta['tracking_link'] }}
@endif
</x-mail::panel>

@if ($additionalContent)
---

{{ $additionalContent }}

@endif

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

If you have any questions, please don't hesitate to contact us.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
