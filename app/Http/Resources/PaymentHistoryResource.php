<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentHistoryResource extends BaseResource
{
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

    protected function formatMarketplaceOrder($order): array
    {

        $orderNumber = $order->reference ?? $order->id;

        $amount = $order->total?->decimal ?? 0;
        $currencyCode = $order->currency?->code ?? 'MYR';

        $formattedAmount = $this->formatAmount($amount, $currencyCode);

        return [
            'id' => $order->id,
            'type' => 'marketplace',
            'title' => "Marketplace #{$orderNumber}",
            'amount' => "-{$formattedAmount}",
            'amount_value' => -abs($amount),
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

    protected function formatSubscriptionPayment($transaction): array
    {
        $amount = (float) ($transaction->amount ?? 0);
        $currencyCode = 'MYR';
        $formattedAmount = $this->formatAmount($amount, $currencyCode);

        $subscriptionName = $transaction->userSubscription?->subscription?->title ?? 'Subscription';
        $isRenewal = str_contains($transaction->notes ?? '', 'renewal') ||
                     ($transaction->meta['type'] ?? '') === 'recurring_renewal';

        $title = $isRenewal
            ? "Subscription Renewal - {$subscriptionName}"
            : "Subscription - {$subscriptionName}";

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
