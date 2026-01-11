<?php

namespace App\Services;

class RevpaySignatureService
{
    /**
     * Generate payment request signature.
     * SHA-512(MerchantKey + MerchantID + ReferenceNumber + Amount + Currency)
     */
    public function generatePaymentSignature(
        string $merchantKey,
        string $merchantId,
        string $referenceNumber,
        string $amount,
        string $currency
    ): string {
        $string = $merchantKey.$merchantId.$referenceNumber.$amount.$currency;

        return strtolower(hash('sha512', $string));
    }

    /**
     * Generate payment response signature.
     * SHA-512(MerchantKey + MerchantID + TransactionID + ResponseCode + ReferenceNumber + Amount + Currency)
     */
    public function generateResponseSignature(
        string $merchantKey,
        string $merchantId,
        string $transactionId,
        string $responseCode,
        string $referenceNumber,
        string $amount,
        string $currency
    ): string {
        $string = $merchantKey.$merchantId.$transactionId.$responseCode.$referenceNumber.$amount.$currency;

        return strtolower(hash('sha512', $string));
    }

    /**
     * Generate refund signature.
     * SHA-512(MerchantKey + MerchantID + ReferenceNumber + RefundAmount + OriginalReferenceNumber)
     */
    public function generateRefundSignature(
        string $merchantKey,
        string $merchantId,
        string $referenceNumber,
        string $refundAmount,
        string $originalReferenceNumber
    ): string {
        $string = $merchantKey.$merchantId.$referenceNumber.$refundAmount.$originalReferenceNumber;

        return strtolower(hash('sha512', $string));
    }

    /**
     * Verify signature match.
     */
    public function verifySignature(string $expected, string $received): bool
    {
        return hash_equals(strtolower($expected), strtolower($received));
    }

    /**
     * Format amount to 2 decimal places (RevPay requirement).
     */
    public function formatAmount(float|int $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
