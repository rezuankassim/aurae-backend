<?php

namespace App\PaymentTypes;

use App\Services\SenangpaySignatureService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Contracts\Transaction as TransactionContract;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;

class SenangpayPayment extends AbstractPayment
{
    protected SenangpaySignatureService $signatureService;

    public function __construct(SenangpaySignatureService $signatureService)
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
        $merchantId = config('services.senangpay.merchant_id');
        $secretKey = config('services.senangpay.secret_key');
        $baseUrl = config('services.senangpay.base_url', 'https://app.senangpay.my');
        $currency = config('services.senangpay.currency', 'MYR');

        // Generate reference number from order
        $referenceNumber = 'ORD-'.date('Y').'-'.str_pad($this->order->id, 5, '0', STR_PAD_LEFT);

        // Format amount to cents
        $amountInCents = $this->signatureService->formatAmount($this->order->total->value / 100);

        // Get customer details
        $customerName = '';
        $customerEmail = '';
        $customerPhone = '';

        if ($this->order->billingAddress) {
            $customerName = $this->order->billingAddress->first_name.' '.$this->order->billingAddress->last_name;
            $customerEmail = $this->order->billingAddress->contact_email;
            $customerPhone = $this->order->billingAddress->contact_phone;
        }

        // Build return URL
        $returnUrl = url('/payment/senangpay/return');

        // Create intent transaction
        Transaction::create([
            'order_id' => $this->order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'senangpay',
            'amount' => $this->order->total,
            'reference' => $referenceNumber,
            'status' => 'pending',
            'notes' => 'Payment intent created',
            'meta' => [
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'amount_cents' => $amountInCents,
            ],
        ]);

        // Build payment URL - SenangPay requires redirect to their payment page
        // Using the standard payment form redirect
        $paymentUrl = $baseUrl.'/payment/form?'.http_build_query([
            'merchant_id' => $merchantId,
            'order_id' => $referenceNumber,
            'name' => $customerName,
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'amount' => $amountInCents,
            'return_url' => $returnUrl,
        ]);

        // Update order meta with reference number
        $this->order->update([
            'status' => $this->config['authorized'] ?? 'payment-pending',
            'meta' => array_merge((array) $this->order->meta, [
                'senangpay_reference' => $referenceNumber,
                'payment_initiated_at' => now()->toIso8601String(),
            ]),
            'placed_at' => now(),
        ]);

        Log::info('SenangPay payment initiated', [
            'order_id' => $this->order->id,
            'reference' => $referenceNumber,
            'amount_cents' => $amountInCents,
        ]);

        $response = new PaymentAuthorize(
            success: true,
            message: 'Payment initiated successfully',
            orderId: $this->order->id,
            paymentType: 'senangpay',
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
            ->where('driver', 'senangpay')
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
            'driver' => 'senangpay',
            'amount' => $amount > 0 ? $amount : $intentTransaction->amount,
            'reference' => $intentTransaction->reference,
            'status' => 'captured',
            'notes' => 'Payment captured',
            'captured_at' => now(),
            'meta' => $transaction->meta,
        ]);

        Log::info('SenangPay payment captured', [
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
        // SenangPay doesn't provide a direct refund API in the documentation
        // Refunds must be processed manually via the dashboard
        Log::warning('SenangPay refund requested', [
            'order_id' => $transaction->order_id,
            'reference' => $transaction->reference,
            'amount' => $amount,
        ]);

        return new PaymentRefund(
            false,
            'Refunds must be processed manually via SenangPay dashboard'
        );
    }
}
