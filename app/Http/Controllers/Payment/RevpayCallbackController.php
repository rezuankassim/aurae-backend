<?php

namespace App\Http\Controllers\Payment;

use App\Events\PaymentCompleted;
use App\Http\Controllers\Controller;
use App\Services\RevpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Payments;
use Lunar\Models\Order;
use Lunar\Models\Transaction;

class RevpayCallbackController extends Controller
{
    protected RevpaySignatureService $signatureService;

    public function __construct(RevpaySignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Handle frontend return URL (mobile WebView will be redirected here).
     */
    public function returnUrl(Request $request)
    {
        $transactionId = $request->input('Transaction_ID');
        $referenceNumber = $request->input('Reference_Number');
        $responseCode = $request->input('Response_Code');
        $amount = $request->input('Amount');
        $currency = $request->input('Currency');
        $signature = $request->input('Signature');
        $bankRedirectUrl = $request->input('Bank_Redirect_URL');

        Log::info('RevPay return URL received', [
            'transaction_id' => $transactionId,
            'reference' => $referenceNumber,
            'response_code' => $responseCode,
        ]);

        // Get order from reference
        $order = Order::whereJsonContains('meta->revpay_reference', $referenceNumber)->first();

        if (! $order) {
            Log::error('RevPay return: Order not found', ['reference' => $referenceNumber]);

            return response()->view('payment.error', [
                'message' => 'Order not found',
            ]);
        }

        // Verify signature
        $merchantKey = config('services.revpay.merchant_key');
        $merchantId = config('services.revpay.merchant_id');

        $expectedSignature = $this->signatureService->generateResponseSignature(
            $merchantKey,
            $merchantId,
            $transactionId,
            $responseCode,
            $referenceNumber,
            $amount,
            $currency
        );

        if (! $this->signatureService->verifySignature($expectedSignature, $signature)) {
            Log::error('RevPay return: Invalid signature', [
                'reference' => $referenceNumber,
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);

            return response()->view('payment.error', [
                'message' => 'Invalid signature',
            ]);
        }

        // Handle Response_Code 09 (redirect to bank)
        if ($responseCode === '09' && $bankRedirectUrl) {
            Log::info('RevPay return: Bank redirect required', [
                'reference' => $referenceNumber,
                'bank_url' => $bankRedirectUrl,
            ]);

            // Broadcast intermediate event to mobile
            if ($order->user_id) {
                broadcast(new PaymentCompleted(
                    userId: $order->user_id,
                    referenceNumber: $referenceNumber,
                    status: 'redirect_required',
                    orderId: $order->id,
                    transactionId: $transactionId,
                    amount: $amount,
                    currency: $currency
                ));
            }

            // Redirect to bank URL
            return redirect()->away($bankRedirectUrl);
        }

        // Determine status from response code
        $status = $responseCode === '00' ? 'success' : 'failed';

        // Broadcast WebSocket event to mobile app
        if ($order->user_id) {
            broadcast(new PaymentCompleted(
                userId: $order->user_id,
                referenceNumber: $referenceNumber,
                status: $status,
                orderId: $order->id,
                transactionId: $transactionId,
                amount: $amount,
                currency: $currency
            ));

            Log::info('RevPay return: WebSocket event broadcasted', [
                'user_id' => $order->user_id,
                'reference' => $referenceNumber,
                'status' => $status,
            ]);
        }

        // Return simple HTML page
        return response()->view('payment.processing', [
            'status' => $status,
            'reference' => $referenceNumber,
        ]);
    }

    /**
     * Handle backend callback (authoritative).
     */
    public function backendCallback(Request $request)
    {
        $transactionId = $request->input('Transaction_ID');
        $referenceNumber = $request->input('Reference_Number');
        $responseCode = $request->input('Response_Code');
        $amount = $request->input('Amount');
        $currency = $request->input('Currency');
        $signature = $request->input('Signature');
        $cardType = $request->input('Card_Type');
        $lastFour = $request->input('Last_Four');

        Log::info('RevPay backend callback received', [
            'transaction_id' => $transactionId,
            'reference' => $referenceNumber,
            'response_code' => $responseCode,
        ]);

        // Get order from reference
        $order = Order::whereJsonContains('meta->revpay_reference', $referenceNumber)->first();

        if (! $order) {
            Log::error('RevPay callback: Order not found', ['reference' => $referenceNumber]);

            return response('Order not found', 404);
        }

        // Verify signature
        $merchantKey = config('services.revpay.merchant_key');
        $merchantId = config('services.revpay.merchant_id');

        $expectedSignature = $this->signatureService->generateResponseSignature(
            $merchantKey,
            $merchantId,
            $transactionId,
            $responseCode,
            $referenceNumber,
            $amount,
            $currency
        );

        if (! $this->signatureService->verifySignature($expectedSignature, $signature)) {
            Log::error('RevPay callback: Invalid signature', [
                'reference' => $referenceNumber,
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);

            return response('Invalid signature', 400);
        }

        // Get intent transaction
        $intentTransaction = Transaction::where('reference', $referenceNumber)
            ->where('type', 'intent')
            ->where('driver', 'revpay')
            ->first();

        if (! $intentTransaction) {
            Log::error('RevPay callback: Intent transaction not found', ['reference' => $referenceNumber]);

            return response('Transaction not found', 404);
        }

        // Check if already processed
        if (Transaction::where('parent_transaction_id', $intentTransaction->id)
            ->where('type', 'capture')
            ->exists()) {
            Log::info('RevPay callback: Already processed', ['reference' => $referenceNumber]);

            return response('OK', 200);
        }

        if ($responseCode === '00') {
            // Payment successful - capture it
            try {
                $paymentDriver = Payments::driver('revpay')->order($order);

                // Create capture transaction with full details
                $captureTransaction = Transaction::create([
                    'parent_transaction_id' => $intentTransaction->id,
                    'order_id' => $order->id,
                    'success' => true,
                    'type' => 'capture',
                    'driver' => 'revpay',
                    'amount' => $intentTransaction->amount,
                    'reference' => $referenceNumber,
                    'status' => 'captured',
                    'notes' => 'Payment captured successfully',
                    'captured_at' => now(),
                    'card_type' => $cardType,
                    'last_four' => $lastFour,
                    'meta' => [
                        'transaction_id' => $transactionId,
                        'response_code' => $responseCode,
                        'response_data' => $request->all(),
                    ],
                ]);

                // Update order status
                $order->update([
                    'status' => 'payment-received',
                    'meta' => array_merge((array) $order->meta, [
                        'revpay_transaction_id' => $transactionId,
                        'payment_completed_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::info('RevPay callback: Payment captured', [
                    'order_id' => $order->id,
                    'reference' => $referenceNumber,
                    'transaction_id' => $transactionId,
                ]);

                // Broadcast success to mobile app
                if ($order->user_id) {
                    broadcast(new PaymentCompleted(
                        userId: $order->user_id,
                        referenceNumber: $referenceNumber,
                        status: 'success',
                        orderId: $order->id,
                        transactionId: $transactionId,
                        amount: $amount,
                        currency: $currency
                    ));
                }
            } catch (\Exception $e) {
                Log::error('RevPay callback: Capture failed', [
                    'reference' => $referenceNumber,
                    'error' => $e->getMessage(),
                ]);

                return response('Capture failed', 500);
            }
        } else {
            // Payment failed
            $order->update([
                'status' => 'payment-failed',
                'meta' => array_merge((array) $order->meta, [
                    'revpay_transaction_id' => $transactionId,
                    'payment_failed_at' => now()->toIso8601String(),
                    'failure_reason' => $request->input('Response_Message', 'Payment failed'),
                ]),
            ]);

            Log::warning('RevPay callback: Payment failed', [
                'order_id' => $order->id,
                'reference' => $referenceNumber,
                'response_code' => $responseCode,
            ]);

            // Broadcast failure to mobile app
            if ($order->user_id) {
                broadcast(new PaymentCompleted(
                    userId: $order->user_id,
                    referenceNumber: $referenceNumber,
                    status: 'failed',
                    orderId: $order->id,
                    transactionId: $transactionId,
                    amount: $amount,
                    currency: $currency
                ));
            }
        }

        // Must return "OK" for RevPay
        return response('OK', 200);
    }
}
