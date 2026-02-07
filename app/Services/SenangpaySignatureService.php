<?php

namespace App\Services;

class SenangpaySignatureService
{
    /**
     * Generate query signature for order status.
     * Format: merchant_id + secret_key + order_id
     */
    public function generateQueryOrderSignature(
        string $merchantId,
        string $secretKey,
        string $orderId
    ): string {
        $string = $merchantId.$secretKey.$orderId;

        return hash_hmac('sha256', $string, $secretKey);
    }

    /**
     * Generate query signature for transaction status.
     * Format: merchant_id + secret_key + transaction_reference
     */
    public function generateQueryTransactionSignature(
        string $merchantId,
        string $secretKey,
        string $transactionReference
    ): string {
        $string = $merchantId.$secretKey.$transactionReference;

        return hash_hmac('sha256', $string, $secretKey);
    }

    /**
     * Generate query signature for transaction list.
     * Format: merchant_id + secret_key + timestamp_start + timestamp_end
     */
    public function generateQueryListSignature(
        string $merchantId,
        string $secretKey,
        int $timestampStart,
        int $timestampEnd
    ): string {
        $string = $merchantId.$secretKey.$timestampStart.$timestampEnd;

        return hash_hmac('sha256', $string, $secretKey);
    }

    /**
     * Verify signature match.
     */
    public function verifySignature(string $expected, string $received): bool
    {
        return hash_equals($expected, $received);
    }

    /**
     * Format amount to integer in cents (SenangPay requirement).
     * E.g., 2.00 becomes 200
     */
    public function formatAmount(float|int $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount from cents back to decimal.
     * E.g., 200 becomes 2.00
     */
    public function formatAmountFromCents(int $amount): float
    {
        return $amount / 100;
    }
}
