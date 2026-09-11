<?php

namespace App\Services;

class SenangpaySignatureService
{
    public function generateQueryOrderSignature(
        string $merchantId,
        string $secretKey,
        string $orderId
    ): string {
        $string = $merchantId.$secretKey.$orderId;

        return hash_hmac('sha256', $string, $secretKey);
    }

    public function generateQueryTransactionSignature(
        string $merchantId,
        string $secretKey,
        string $transactionReference
    ): string {
        $string = $merchantId.$secretKey.$transactionReference;

        return hash_hmac('sha256', $string, $secretKey);
    }

    public function generateQueryListSignature(
        string $merchantId,
        string $secretKey,
        int $timestampStart,
        int $timestampEnd
    ): string {
        $string = $merchantId.$secretKey.$timestampStart.$timestampEnd;

        return hash_hmac('sha256', $string, $secretKey);
    }

    public function verifySignature(string $expected, string $received): bool
    {
        return hash_equals($expected, $received);
    }

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

    public function generatePaymentHash(
        string $secretKey,
        string $detail,
        string $amount,
        string $orderId
    ): string {
        $string = $secretKey.$detail.$amount.$orderId;

        return hash_hmac('sha256', $string, $secretKey);
    }

    public function formatAmount(int $amountInCents): string
    {
        return number_format($amountInCents / 100, 2, '.', '');
    }

    public function formatAmountFromCents(int $amount): float
    {
        return $amount / 100;
    }

    public function generateRecurringPaymentHash(
        string $secretKey,
        string $orderId,
        string $recurringId
    ): string {
        $string = $secretKey.$recurringId.$orderId;

        return hash('sha256', $string);
    }

    public function generateRecurringReturnHash(
        string $secretKey,
        string $statusId,
        string $orderId,
        string $transactionId,
        string $msg
    ): string {
        $string = $secretKey.$statusId.$orderId.$transactionId.$msg;

        return hash('sha256', $string);
    }
}
