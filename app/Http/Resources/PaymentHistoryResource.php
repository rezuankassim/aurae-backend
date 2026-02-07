<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentHistoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = $this->resource['type'];
        $data = $this->resource['data'];

        if ($type === 'marketplace') {
            return $this->formatMarketplaceOrder($data);
        }

        // Add other payment types here when implemented
        // if ($type === 'subscription') {
        //     return $this->formatSubscriptionFee($data);
        // }

        return [];
    }

    /**
     * Format marketplace order for payment history.
     *
     * @param  \Lunar\Models\Order  $order
     */
    protected function formatMarketplaceOrder($order): array
    {
        // Get the order reference/number (e.g., "12345")
        $orderNumber = $order->reference ?? $order->id;

        // Get the total amount (negative for display as expense)
        $amount = $order->total?->decimal ?? 0;
        $currencyCode = $order->currency?->code ?? 'MYR';

        // Format the amount with currency symbol
        $formattedAmount = $this->formatAmount($amount, $currencyCode);

        return [
            'id' => $order->id,
            'type' => 'marketplace',
            'title' => "market place #{$orderNumber}",
            'amount' => "-{$formattedAmount}", // Negative because it's an expense
            'amount_value' => -abs($amount), // Numeric value for calculations
            'currency' => $currencyCode,
            'status' => $order->status,
            'date' => $order->placed_at instanceof \Carbon\Carbon
                ? $order->placed_at->toIso8601String()
                : ($order->created_at instanceof \Carbon\Carbon
                    ? $order->created_at->toIso8601String()
                    : $order->created_at),
            'reference' => $orderNumber,
        ];
    }

    /**
     * Format subscription fee for payment history.
     * To be implemented when subscription module is ready.
     *
     * @param  mixed  $subscription
     * @return array
     */
    // protected function formatSubscriptionFee($subscription): array
    // {
    //     $amount = $subscription->amount ?? 0;
    //     $currencyCode = 'MYR';
    //     $formattedAmount = $this->formatAmount($amount, $currencyCode);
    //
    //     return [
    //         'id' => $subscription->id,
    //         'type' => 'subscription',
    //         'title' => 'subscription fee',
    //         'amount' => "-{$formattedAmount}",
    //         'amount_value' => -abs($amount),
    //         'currency' => $currencyCode,
    //         'status' => $subscription->status,
    //         'date' => $subscription->created_at->toIso8601String(),
    //         'reference' => $subscription->reference ?? $subscription->id,
    //     ];
    // }

    /**
     * Format amount with currency symbol.
     */
    protected function formatAmount(float $amount, string $currencyCode): string
    {
        $currencySymbols = [
            'MYR' => 'RM',
            'SGD' => 'S$',
            'USD' => '$',
            'EUR' => '€',
        ];

        $symbol = $currencySymbols[$currencyCode] ?? $currencyCode;

        return $symbol.number_format(abs($amount), 2);
    }
}
