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

        if ($type === 'subscription') {
            return $this->formatSubscriptionPayment($data);
        }

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
     * Format subscription payment for payment history.
     *
     * @param  \App\Models\SubscriptionTransaction  $transaction
     */
    protected function formatSubscriptionPayment($transaction): array
    {
        $amount = (float) ($transaction->amount ?? 0);
        $currencyCode = 'MYR';
        $formattedAmount = $this->formatAmount($amount, $currencyCode);

        // Determine the title based on transaction type
        $subscriptionName = $transaction->userSubscription?->subscription?->title ?? 'Subscription';
        $isRenewal = str_contains($transaction->notes ?? '', 'renewal') ||
                     ($transaction->meta['type'] ?? '') === 'recurring_renewal';

        $title = $isRenewal
            ? "subscription renewal - {$subscriptionName}"
            : "subscription - {$subscriptionName}";

        return [
            'id' => $transaction->id,
            'type' => 'subscription',
            'title' => $title,
            'amount' => "-{$formattedAmount}",
            'amount_value' => -abs($amount),
            'currency' => $currencyCode,
            'status' => $transaction->status,
            'date' => $transaction->captured_at instanceof \Carbon\Carbon
                ? $transaction->captured_at->toIso8601String()
                : ($transaction->created_at instanceof \Carbon\Carbon
                    ? $transaction->created_at->toIso8601String()
                    : $transaction->created_at),
            'reference' => $transaction->reference ?? $transaction->id,
            'subscription_name' => $subscriptionName,
            'is_renewal' => $isRenewal,
        ];
    }

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
