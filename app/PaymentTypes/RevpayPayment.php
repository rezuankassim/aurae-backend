<?php

namespace App\PaymentTypes;

use App\Services\RevpaySignatureService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;

class RevpayPayment extends AbstractPayment
{
    protected RevpaySignatureService $signatureService;

    public function __construct(RevpaySignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * {@inheritDoc}
     */
    public function authorize(): ?PaymentAuthorize
    {
        // Ensure order exists
        if (! $this->order) {
            if (! $this->order = $this->cart?->draftOrder()->first()) {
                $this->order = $this->cart->createOrder();
            }
        }

        // Get config
        $merchantId = config('services.revpay.merchant_id');
        $merchantKey = config('services.revpay.merchant_key');
        $keyIndex = config('services.revpay.key_index');
        $baseUrl = config('services.revpay.base_url');
        $currency = config('services.revpay.currency', 'MYR');

        // Generate reference number from order
        $referenceNumber = 'ORD-'.date('Y').'-'.str_pad($this->order->id, 5, '0', STR_PAD_LEFT);

        // Format amount to 2 decimals
        $amount = $this->signatureService->formatAmount($this->order->total->value / 100);

        // Generate signature
        $signature = $this->signatureService->generatePaymentSignature(
            $merchantKey,
            $merchantId,
            $referenceNumber,
            $amount,
            $currency
        );

        // Get customer IP
        $customerIp = request()->ip();

        // Build return and backend URLs
        $returnUrl = url('/payment/revpay/return');
        $backendUrl = url('/payment/revpay/callback');

        // Build payment request parameters
        $params = [
            'Revpay_Merchant_ID' => $merchantId,
            'Reference_Number' => $referenceNumber,
            'Amount' => $amount,
            'Currency' => $currency,
            'Customer_IP' => $customerIp,
            'Return_URL' => $returnUrl,
            'Backend_URL' => $backendUrl,
            'Key_Index' => $keyIndex,
            'Signature' => $signature,
            'Transaction_Description' => 'Order #'.$this->order->id,
        ];

        // Add customer details if available
        if ($this->order->billingAddress) {
            $params['Customer_Name'] = $this->order->billingAddress->first_name.' '.$this->order->billingAddress->last_name;
            $params['Customer_Email'] = $this->order->billingAddress->contact_email;
            $params['Customer_Contact'] = $this->order->billingAddress->contact_phone;
        }

        // Create intent transaction
        Transaction::create([
            'order_id' => $this->order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'revpay',
            'amount' => $this->order->total,
            'reference' => $referenceNumber,
            'status' => 'pending',
            'notes' => 'Payment intent created',
            'meta' => [
                'payment_params' => $params,
            ],
        ]);

        // Build payment URL
        $paymentUrl = $baseUrl.'/payment?'.http_build_query($params);

        // Update order meta with reference number and user_id for WebSocket
        $this->order->update([
            'status' => $this->config['authorized'] ?? 'payment-pending',
            'meta' => array_merge((array) $this->order->meta, [
                'revpay_reference' => $referenceNumber,
                'payment_initiated_at' => now()->toIso8601String(),
            ]),
            'placed_at' => now(),
        ]);

        Log::info('RevPay payment initiated', [
            'order_id' => $this->order->id,
            'reference' => $referenceNumber,
            'amount' => $amount,
        ]);

        $response = new PaymentAuthorize(
            success: true,
            message: 'Payment initiated successfully',
            orderId: $this->order->id,
            paymentType: 'revpay',
        );

        // Store payment URL in data for mobile app
        $response->data = [
            'payment_url' => $paymentUrl,
            'reference_number' => $referenceNumber,
        ];

        PaymentAttemptEvent::dispatch($response);

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function capture(TransactionContract $transaction, $amount = 0): PaymentCapture
    {
        // Get intent transaction
        $intentTransaction = Transaction::where('order_id', $transaction->order_id)
            ->where('type', 'intent')
            ->where('driver', 'revpay')
            ->first();

        if (! $intentTransaction) {
            return new PaymentCapture(
                success: false,
                message: 'Intent transaction not found'
            );
        }

        // Create capture transaction
        Transaction::create([
            'parent_transaction_id' => $intentTransaction->id,
            'order_id' => $transaction->order_id,
            'success' => true,
            'type' => 'capture',
            'driver' => 'revpay',
            'amount' => $amount > 0 ? $amount : $intentTransaction->amount,
            'reference' => $intentTransaction->reference,
            'status' => 'captured',
            'notes' => 'Payment captured',
            'captured_at' => now(),
            'meta' => $transaction->meta,
        ]);

        Log::info('RevPay payment captured', [
            'order_id' => $transaction->order_id,
            'reference' => $intentTransaction->reference,
            'amount' => $amount,
        ]);

        return new PaymentCapture(
            success: true,
            message: 'Payment captured successfully'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function refund(TransactionContract $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        $merchantId = config('services.revpay.merchant_id');
        $merchantKey = config('services.revpay.merchant_key');
        $keyIndex = config('services.revpay.key_index');
        $baseUrl = config('services.revpay.base_url');

        // Generate new reference for refund
        $refundReference = 'REF-'.date('Y').'-'.str_pad($transaction->order_id, 5, '0', STR_PAD_LEFT).'-'.time();
        $originalReference = $transaction->reference;

        // Format refund amount
        $refundAmount = $this->signatureService->formatAmount($amount > 0 ? $amount / 100 : $transaction->amount->value / 100);

        // Generate refund signature
        $signature = $this->signatureService->generateRefundSignature(
            $merchantKey,
            $merchantId,
            $refundReference,
            $refundAmount,
            $originalReference
        );

        try {
            // Call RevPay refund API
            $response = Http::post($baseUrl.'/refund', [
                'Revpay_Merchant_ID' => $merchantId,
                'Reference_Number' => $refundReference,
                'Refund_Amount' => $refundAmount,
                'Original_Reference_Number' => $originalReference,
                'Key_Index' => $keyIndex,
                'Signature' => $signature,
            ]);

            $result = $response->json();

            if ($response->successful() && ($result['Response_Code'] ?? '') === '00') {
                // Create refund transaction
                Transaction::create([
                    'parent_transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                    'success' => true,
                    'type' => 'refund',
                    'driver' => 'revpay',
                    'amount' => $amount > 0 ? $amount : $transaction->amount,
                    'reference' => $refundReference,
                    'status' => 'refunded',
                    'notes' => $notes ?? 'Payment refunded',
                    'meta' => [
                        'refund_response' => $result,
                    ],
                ]);

                Log::info('RevPay refund successful', [
                    'order_id' => $transaction->order_id,
                    'refund_reference' => $refundReference,
                    'amount' => $refundAmount,
                ]);

                return new PaymentRefund(true, 'Refund processed successfully');
            }

            Log::error('RevPay refund failed', [
                'order_id' => $transaction->order_id,
                'response' => $result,
            ]);

            return new PaymentRefund(false, $result['Response_Message'] ?? 'Refund failed');
        } catch (\Exception $e) {
            Log::error('RevPay refund exception', [
                'order_id' => $transaction->order_id,
                'error' => $e->getMessage(),
            ]);

            return new PaymentRefund(false, 'Refund failed: '.$e->getMessage());
        }
    }
}
