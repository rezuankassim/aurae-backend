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
     * Generate hash for return URL verification.
     * Format: hash_hmac('sha256', secret_key + status_id + order_id + transaction_id + msg, secret_key)
     */
    public function generateReturnHash(
        string $secretKey,
        string $statusId,
        string $orderId,
        string $transactionId,
        string $msg
    ): string {
        $string = $secretKey.$statusId.$orderId.$transactionId.$msg;

        return hash_hmac('sha256', $string, $secretKey);
    }

    /**
     * Generate hash for payment form.
     * Format: hash_hmac('sha256', secret_key + detail + amount + order_id, secret_key)
     */
    public function generatePaymentHash(
        string $secretKey,
        string $detail,
        string $amount,
        string $orderId
    ): string {
        $string = $secretKey.$detail.$amount.$orderId;

        return hash_hmac('sha256', $string, $secretKey);
    }

    /**
     * Format amount to decimal string (SenangPay requirement).
     * E.g., 3490 cents becomes "34.90"
     */
    public function formatAmount(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2, '.', '');
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
