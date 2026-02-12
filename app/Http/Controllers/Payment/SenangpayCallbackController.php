<?php

namespace App\Http\Controllers\Payment;

use App\Events\PaymentCompleted;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use App\Services\SenangpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Transaction;

class SenangpayCallbackController extends Controller
{
    protected SenangpaySignatureService $signatureService;

    public function __construct(SenangpaySignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    /**
     * Handle return URL after payment completion.
     * SenangPay redirects here with status_id parameter.
     */
    public function returnUrl(Request $request)
    {
        $statusId = $request->input('status_id');
        $orderId = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $msg = $request->input('msg');
        $hash = $request->input('hash');

        Log::info('SenangPay return URL received', [
            'status_id' => $statusId,
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'msg' => $msg,
            'hash' => $hash,
        ]);

        // Check if this is a subscription payment
        $transaction = Transaction::where('reference', $orderId)
            ->where('driver', 'senangpay')
            ->where('type', 'intent')
            ->first();

        $isSubscription = $transaction && isset($transaction->meta['type']) && $transaction->meta['type'] === 'subscription';

        if ($isSubscription) {
            return $this->handleSubscriptionPayment($statusId, $orderId, $transactionId, $msg, $hash, $transaction);
        }

        // Get order by reference number stored in meta
        $order = Order::whereJsonContains('meta->senangpay_reference', $orderId)->first();

        if (! $order) {
            Log::error('SenangPay return: Order not found', ['order_id' => $orderId]);

            return response()->view('payment.error', [
                'message' => 'Order not found',
            ]);
        }

        // Verify hash from return URL parameters
        $secretKey = config('services.senangpay.secret_key');
        $expectedHash = $this->signatureService->generateReturnHash(
            $secretKey,
            $statusId,
            $orderId,
            $transactionId,
            $msg
        );

        if (! $this->signatureService->verifySignature($expectedHash, $hash)) {
            Log::error('SenangPay return: Hash verification failed', [
                'order_id' => $orderId,
                'expected_hash' => $expectedHash,
                'received_hash' => $hash,
            ]);

            return response()->view('payment.error', [
                'message' => 'Payment verification failed',
            ]);
        }

        // Determine status based on status_id (1 = success, 0 = failed)
        $status = $statusId === '1' ? 'success' : 'failed';

        // Broadcast WebSocket event to mobile app
        if ($order->user_id) {
            broadcast(new PaymentCompleted(
                userId: $order->user_id,
                referenceNumber: $orderId,
                status: $status,
                orderId: $order->id,
                transactionId: $transactionId,
                amount: $order->total->formatted_amount,
                currency: 'MYR'
            ));

            Log::info('SenangPay return: WebSocket event broadcasted', [
                'user_id' => $order->user_id,
                'order_id' => $orderId,
                'status' => $status,
            ]);
        }

        // If payment successful, capture it
        if ($status === 'success') {
            try {
                $this->capturePayment($order, $orderId, $transactionId);
            } catch (\Exception $e) {
                Log::error('SenangPay return: Capture failed', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Payment failed - update order status
            $order->update([
                'status' => 'payment-failed',
                'meta' => array_merge((array) $order->meta, [
                    'senangpay_transaction_id' => $transactionId,
                    'payment_failed_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::warning('SenangPay return: Payment failed', [
                'order_id' => $order->id,
                'reference' => $orderId,
            ]);
        }

        // Return simple HTML page
        return response()->view('payment.processing', [
            'status' => $status,
            'reference' => $orderId,
        ]);
    }

    /**
     * Query payment status from SenangPay API.
     */
    protected function queryPaymentStatus(string $orderId): ?array
    {
        try {
            $merchantId = config('services.senangpay.merchant_id');
            $secretKey = config('services.senangpay.secret_key');
            $baseUrl = config('services.senangpay.base_url', 'https://app.senangpay.my');

            // Generate signature
            $signature = $this->signatureService->generateQueryOrderSignature(
                $merchantId,
                $secretKey,
                $orderId
            );

            // Query order status
            $response = Http::withBasicAuth($merchantId, '')
                ->get($baseUrl.'/apiv1/query_order_status', [
                    'merchant_id' => $merchantId,
                    'order_id' => $orderId,
                    'hash' => $signature,
                ]);

            if (! $response->successful()) {
                Log::error('SenangPay query failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $result = $response->json();

            if (isset($result['status']) && $result['status'] === 1) {
                // Success - return first transaction data
                if (isset($result['data']) && is_array($result['data']) && count($result['data']) > 0) {
                    return $result['data'][0];
                }

                return $result;
            }

            Log::warning('SenangPay query: Not found or failed', [
                'order_id' => $orderId,
                'response' => $result,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SenangPay query exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Capture payment after successful status verification.
     */
    protected function capturePayment(Order $order, string $orderId, string $transactionId): void
    {
        // Get intent transaction
        $intentTransaction = Transaction::where('reference', $orderId)
            ->where('type', 'intent')
            ->where('driver', 'senangpay')
            ->first();

        if (! $intentTransaction) {
            Log::error('SenangPay capture: Intent transaction not found', ['order_id' => $orderId]);

            return;
        }

        // Check if already processed
        if (Transaction::where('parent_transaction_id', $intentTransaction->id)
            ->where('type', 'capture')
            ->exists()) {
            Log::info('SenangPay capture: Already processed', ['order_id' => $orderId]);

            return;
        }

        // Create capture transaction
        Transaction::create([
            'parent_transaction_id' => $intentTransaction->id,
            'order_id' => $order->id,
            'success' => true,
            'type' => 'capture',
            'driver' => 'senangpay',
            'amount' => $intentTransaction->amount,
            'reference' => $orderId,
            'status' => 'captured',
            'card_type' => '',
            'last_four' => '',
            'notes' => 'Payment captured successfully',
            'captured_at' => now(),
            'meta' => [
                'transaction_id' => $transactionId,
            ],
        ]);

        // Update order status
        $order->update([
            'status' => 'payment-received',
            'meta' => array_merge((array) $order->meta, [
                'senangpay_transaction_id' => $transactionId,
                'payment_completed_at' => now()->toIso8601String(),
            ]),
        ]);

        // Complete and delete user's cart
        if ($order->user_id) {
            $cart = Cart::where('user_id', $order->user_id)
                ->whereNull('completed_at')
                ->first();

            if ($cart) {
                $cart->update(['completed_at' => now()]);
                $cart->delete();

                Log::info('SenangPay capture: Cart completed and deleted', [
                    'cart_id' => $cart->id,
                    'user_id' => $order->user_id,
                ]);
            }
        }

        Log::info('SenangPay capture: Payment captured', [
            'order_id' => $order->id,
            'reference' => $orderId,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Handle subscription payment callback.
     */
    protected function handleSubscriptionPayment(
        string $statusId,
        string $orderId,
        string $transactionId,
        string $msg,
        string $hash,
        Transaction $intentTransaction
    ) {
        // Verify hash
        $secretKey = config('services.senangpay.secret_key');
        $expectedHash = $this->signatureService->generateReturnHash(
            $secretKey,
            $statusId,
            $orderId,
            $transactionId,
            $msg
        );

        if (! $this->signatureService->verifySignature($expectedHash, $hash)) {
            Log::error('SenangPay subscription: Hash verification failed', [
                'order_id' => $orderId,
                'expected_hash' => $expectedHash,
                'received_hash' => $hash,
            ]);

            return response()->view('payment.error', [
                'message' => 'Payment verification failed',
            ]);
        }

        // Get user subscription
        $userSubscriptionId = $intentTransaction->meta['user_subscription_id'] ?? null;
        if (! $userSubscriptionId) {
            Log::error('SenangPay subscription: User subscription ID not found', ['order_id' => $orderId]);

            return response()->view('payment.error', [
                'message' => 'Subscription not found',
            ]);
        }

        $userSubscription = UserSubscription::find($userSubscriptionId);
        if (! $userSubscription) {
            Log::error('SenangPay subscription: User subscription not found', [
                'order_id' => $orderId,
                'user_subscription_id' => $userSubscriptionId,
            ]);

            return response()->view('payment.error', [
                'message' => 'Subscription not found',
            ]);
        }

        // Determine status
        $status = $statusId === '1' ? 'success' : 'failed';

        if ($status === 'success') {
            // Check if already captured
            if (! Transaction::where('parent_transaction_id', $intentTransaction->id)
                ->where('type', 'capture')
                ->exists()) {
                // Create capture transaction
                Transaction::create([
                    'parent_transaction_id' => $intentTransaction->id,
                    'order_id' => null,
                    'success' => true,
                    'type' => 'capture',
                    'driver' => 'senangpay',
                    'amount' => $intentTransaction->amount,
                    'reference' => $orderId,
                    'status' => 'captured',
                    'card_type' => '',
                    'last_four' => '',
                    'notes' => 'Subscription payment captured',
                    'captured_at' => now(),
                    'meta' => [
                        'transaction_id' => $transactionId,
                        'type' => 'subscription',
                        'subscription_id' => $intentTransaction->meta['subscription_id'],
                        'user_subscription_id' => $userSubscriptionId,
                    ],
                ]);

                // Update user subscription
                $userSubscription->update([
                    'status' => 'active',
                    'payment_status' => 'completed',
                    'paid_at' => now(),
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                ]);

                Log::info('SenangPay subscription: Payment captured', [
                    'user_subscription_id' => $userSubscriptionId,
                    'reference' => $orderId,
                    'transaction_id' => $transactionId,
                ]);
            }
        } else {
            // Payment failed
            $userSubscription->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
            ]);

            Log::warning('SenangPay subscription: Payment failed', [
                'user_subscription_id' => $userSubscriptionId,
                'reference' => $orderId,
            ]);
        }

        return response()->view('payment.processing', [
            'status' => $status,
            'reference' => $orderId,
        ]);
    }

    /**
     * Handle recurring payment return URL.
     */
    public function recurringReturnUrl(Request $request)
    {
        $statusId = $request->input('status_id', '');
        $orderId = $request->input('order_id', '');
        $transactionId = $request->input('transaction_id', '');
        $msg = $request->input('msg', '');
        $hash = $request->input('hash', '');

        Log::info('SenangPay recurring return URL received', [
            'status_id' => $statusId,
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'msg' => $msg,
            'hash' => $hash,
            'all_params' => $request->all(),
        ]);

        // Find the transaction
        $transaction = SubscriptionTransaction::where('reference', $orderId)
            ->where('driver', 'senangpay')
            ->where('type', 'intent')
            ->first();

        if (! $transaction) {
            Log::error('SenangPay recurring return: Transaction not found', ['order_id' => $orderId]);

            return response()->view('payment.error', [
                'message' => 'Transaction not found',
            ]);
        }

        // Verify hash using recurring hash method
        $secretKey = config('services.senangpay.secret_key');
        $expectedHash = $this->signatureService->generateRecurringReturnHash(
            $secretKey,
            $statusId,
            $orderId,
            $transactionId,
            $msg
        );

        if (! $this->signatureService->verifySignature($expectedHash, $hash)) {
            Log::error('SenangPay recurring return: Hash verification failed', [
                'order_id' => $orderId,
                'expected_hash' => $expectedHash,
                'received_hash' => $hash,
            ]);

            return response()->view('payment.error', [
                'message' => 'Payment verification failed',
            ]);
        }

        $userSubscription = UserSubscription::find($transaction->user_subscription_id);
        if (! $userSubscription) {
            Log::error('SenangPay recurring return: User subscription not found', [
                'order_id' => $orderId,
                'user_subscription_id' => $transaction->user_subscription_id,
            ]);

            return response()->view('payment.error', [
                'message' => 'Subscription not found',
            ]);
        }

        // Determine status
        $status = $statusId === '1' ? 'success' : 'failed';

        if ($status === 'success') {
            // Check if already captured
            if (! SubscriptionTransaction::where('parent_transaction_id', $transaction->id)
                ->where('type', 'capture')
                ->exists()) {
                // Create capture transaction
                SubscriptionTransaction::create([
                    'parent_transaction_id' => $transaction->id,
                    'user_subscription_id' => $transaction->user_subscription_id,
                    'success' => true,
                    'type' => 'capture',
                    'driver' => 'senangpay',
                    'amount' => $transaction->amount,
                    'reference' => $orderId,
                    'status' => 'captured',
                    'notes' => 'Recurring subscription payment captured',
                    'captured_at' => now(),
                    'meta' => [
                        'transaction_id' => $transactionId,
                        'type' => 'recurring_subscription',
                        'subscription_id' => $transaction->meta['subscription_id'] ?? null,
                    ],
                ]);

                // Update user subscription
                $userSubscription->update([
                    'status' => 'active',
                    'payment_status' => 'completed',
                    'paid_at' => now(),
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'next_billing_at' => now()->addMonth(),
                ]);

                Log::info('SenangPay recurring return: Payment captured', [
                    'user_subscription_id' => $transaction->user_subscription_id,
                    'reference' => $orderId,
                    'transaction_id' => $transactionId,
                ]);
            }
        } else {
            // Payment failed
            $userSubscription->update([
                'status' => 'cancelled',
                'payment_status' => 'failed',
            ]);

            Log::warning('SenangPay recurring return: Payment failed', [
                'user_subscription_id' => $transaction->user_subscription_id,
                'reference' => $orderId,
            ]);
        }

        return response()->view('payment.processing', [
            'status' => $status,
            'reference' => $orderId,
        ]);
    }

    /**
     * Handle recurring payment callback (for subsequent charges).
     */
    public function recurringCallback(Request $request)
    {
        $statusId = $request->input('status_id');
        $orderId = $request->input('order_id');
        $transactionId = $request->input('transaction_id');
        $msg = $request->input('msg');
        $hash = $request->input('hash');

        Log::info('SenangPay recurring callback received', [
            'status_id' => $statusId,
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'msg' => $msg,
            'all_params' => $request->all(),
        ]);

        // Verify hash
        $secretKey = config('services.senangpay.secret_key');
        $expectedHash = $this->signatureService->generateRecurringReturnHash(
            $secretKey,
            $statusId,
            $orderId,
            $transactionId,
            $msg
        );

        if (! $this->signatureService->verifySignature($expectedHash, $hash)) {
            Log::error('SenangPay recurring callback: Hash verification failed', [
                'order_id' => $orderId,
                'expected_hash' => $expectedHash,
                'received_hash' => $hash,
            ]);

            // Still return OK to acknowledge receipt, but log the error
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        // Find the original transaction by order_id pattern
        $transaction = SubscriptionTransaction::where('reference', $orderId)
            ->where('driver', 'senangpay')
            ->where('type', 'intent')
            ->first();

        if (! $transaction) {
            // This might be a subsequent recurring charge - find by user subscription
            Log::info('SenangPay recurring callback: Original transaction not found, checking for renewal', [
                'order_id' => $orderId,
            ]);

            // Try to find user subscription by reference pattern
            $userSubscription = UserSubscription::where('transaction_id', 'LIKE', 'SUB-%')
                ->where('is_recurring', true)
                ->where('status', 'active')
                ->whereNull('cancelled_at')
                ->first();

            if ($userSubscription && $statusId === '1') {
                // This is a renewal payment
                $this->handleRecurringRenewal($userSubscription, $transactionId, $orderId);

                return response('OK', 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('SenangPay recurring callback: Could not find matching subscription', [
                'order_id' => $orderId,
            ]);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        // Get user subscription
        $userSubscription = UserSubscription::find($transaction->user_subscription_id);

        if (! $userSubscription) {
            Log::error('SenangPay recurring callback: User subscription not found', [
                'order_id' => $orderId,
                'user_subscription_id' => $transaction->user_subscription_id,
            ]);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $status = $statusId === '1' ? 'success' : 'failed';

        if ($status === 'success') {
            $this->handleRecurringRenewal($userSubscription, $transactionId, $orderId);
        } else {
            // Payment failed - mark subscription as expired or handle retry logic
            Log::warning('SenangPay recurring callback: Renewal payment failed', [
                'user_subscription_id' => $transaction->user_subscription_id,
                'order_id' => $orderId,
            ]);

            // Optionally mark as expired if payment fails
            if ($userSubscription->ends_at && $userSubscription->ends_at->isPast()) {
                $userSubscription->update([
                    'status' => 'expired',
                    'payment_status' => 'failed',
                ]);
            }
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle recurring subscription renewal.
     */
    protected function handleRecurringRenewal(UserSubscription $userSubscription, string $transactionId, string $orderId): void
    {
        // Create a new transaction record for this renewal
        SubscriptionTransaction::create([
            'user_subscription_id' => $userSubscription->id,
            'success' => true,
            'type' => 'capture',
            'driver' => 'senangpay',
            'amount' => $userSubscription->subscription->price ?? 0,
            'reference' => 'RENEWAL-'.$orderId.'-'.now()->format('YmdHis'),
            'status' => 'captured',
            'notes' => 'Recurring subscription renewal payment',
            'captured_at' => now(),
            'meta' => [
                'transaction_id' => $transactionId,
                'type' => 'recurring_renewal',
                'subscription_id' => $userSubscription->subscription_id,
                'original_order_id' => $orderId,
            ],
        ]);

        // Extend subscription
        $userSubscription->update([
            'ends_at' => now()->addMonth(),
            'next_billing_at' => now()->addMonth(),
            'paid_at' => now(),
            'status' => 'active',
            'payment_status' => 'completed',
        ]);

        Log::info('SenangPay recurring callback: Subscription renewed', [
            'user_subscription_id' => $userSubscription->id,
            'new_ends_at' => $userSubscription->ends_at,
            'transaction_id' => $transactionId,
        ]);
    }
}
