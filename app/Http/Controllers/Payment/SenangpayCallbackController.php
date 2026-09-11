<?php

namespace App\Http\Controllers\Payment;

use App\Events\PaymentCompleted;
use App\Http\Controllers\Controller;
use App\Mail\Orders\OrderInvoiceMail;
use App\Mail\Orders\OrderReceivedAdminMail;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use App\Services\SenangpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;
use Lunar\Models\Transaction;

class SenangpayCallbackController extends Controller
{
    protected SenangpaySignatureService $signatureService;

    public function __construct(SenangpaySignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

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

        $transaction = Transaction::where('reference', $orderId)
            ->where('driver', 'senangpay')
            ->where('type', 'intent')
            ->first();

        $isSubscription = $transaction && isset($transaction->meta['type']) && $transaction->meta['type'] === 'subscription';

        if ($isSubscription) {
            return $this->handleSubscriptionPayment($statusId, $orderId, $transactionId, $msg, $hash, $transaction);
        }

        $order = Order::whereJsonContains('meta->senangpay_reference', $orderId)->first();

        if (! $order) {
            Log::error('SenangPay return: Order not found', ['order_id' => $orderId]);

            return response()->view('payment.error', [
                'message' => 'Order not found',
            ]);
        }

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

        $status = $statusId === '1' ? 'success' : 'failed';

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

        return response()->view('payment.processing', [
            'status' => $status,
            'reference' => $orderId,
        ]);
    }

    protected function queryPaymentStatus(string $orderId): ?array
    {
        try {
            $merchantId = config('services.senangpay.merchant_id');
            $secretKey = config('services.senangpay.secret_key');
            $baseUrl = config('services.senangpay.base_url', 'https://app.senangpay.my');

            $signature = $this->signatureService->generateQueryOrderSignature(
                $merchantId,
                $secretKey,
                $orderId
            );

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

    protected function capturePayment(Order $order, string $orderId, string $transactionId): void
    {

        $intentTransaction = Transaction::where('reference', $orderId)
            ->where('type', 'intent')
            ->where('driver', 'senangpay')
            ->first();

        if (! $intentTransaction) {
            Log::error('SenangPay capture: Intent transaction not found', ['order_id' => $orderId]);

            return;
        }

        if (Transaction::where('parent_transaction_id', $intentTransaction->id)
            ->where('type', 'capture')
            ->exists()) {
            Log::info('SenangPay capture: Already processed', ['order_id' => $orderId]);

            return;
        }

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

        $order->update([
            'status' => 'payment-received',
            'cart_id' => null,
            'meta' => array_merge((array) $order->meta, [
                'senangpay_transaction_id' => $transactionId,
                'payment_completed_at' => now()->toIso8601String(),
            ]),
        ]);

        foreach ($order->lines()->where('purchasable_type', 'product_variant')->get() as $line) {
            $variant = ProductVariant::find($line->purchasable_id);

            if ($variant) {
                $variant->decrement('stock', $line->quantity);

                Log::info('SenangPay capture: Stock decremented', [
                    'order_id' => $order->id,
                    'variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'quantity' => $line->quantity,
                    'new_stock' => $variant->fresh()->stock,
                ]);
            }
        }

        Log::info('SenangPay capture: Payment captured', [
            'order_id' => $order->id,
            'reference' => $orderId,
            'transaction_id' => $transactionId,
        ]);

        $customerEmail = $order->billingAddress?->contact_email
            ?? $order->shippingAddress?->contact_email
            ?? $order->user?->email;

        if ($customerEmail) {
            Mail::to($customerEmail)->queue(new OrderInvoiceMail($order));

            Log::info('SenangPay capture: Invoice email queued', [
                'order_id' => $order->id,
                'email' => $customerEmail,
            ]);
        } else {
            Log::warning('SenangPay capture: No customer email found, invoice not sent', [
                'order_id' => $order->id,
            ]);
        }

        $adminEmail = config('mail.admin_order_email');

        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new OrderReceivedAdminMail($order));

            Log::info('SenangPay capture: Admin notification email queued', [
                'order_id' => $order->id,
                'admin_email' => $adminEmail,
            ]);
        }
    }

    protected function handleSubscriptionPayment(
        string $statusId,
        string $orderId,
        string $transactionId,
        string $msg,
        string $hash,
        Transaction $intentTransaction
    ) {

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

        $status = $statusId === '1' ? 'success' : 'failed';

        if ($status === 'success') {

            if (! Transaction::where('parent_transaction_id', $intentTransaction->id)
                ->where('type', 'capture')
                ->exists()) {

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

        $status = $statusId === '1' ? 'success' : 'failed';

        if ($status === 'success') {

            if (! SubscriptionTransaction::where('parent_transaction_id', $transaction->id)
                ->where('type', 'capture')
                ->exists()) {

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

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        $transaction = SubscriptionTransaction::where('reference', $orderId)
            ->where('driver', 'senangpay')
            ->where('type', 'intent')
            ->first();

        if (! $transaction) {

            Log::info('SenangPay recurring callback: Original transaction not found, checking for renewal', [
                'order_id' => $orderId,
            ]);

            $userSubscription = UserSubscription::where('transaction_id', 'LIKE', 'SUB-%')
                ->where('is_recurring', true)
                ->where('status', 'active')
                ->whereNull('cancelled_at')
                ->first();

            if ($userSubscription && $statusId === '1') {

                $this->handleRecurringRenewal($userSubscription, $transactionId, $orderId);

                return response('OK', 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('SenangPay recurring callback: Could not find matching subscription', [
                'order_id' => $orderId,
            ]);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

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

            Log::warning('SenangPay recurring callback: Renewal payment failed', [
                'user_subscription_id' => $transaction->user_subscription_id,
                'order_id' => $orderId,
            ]);

            if ($userSubscription->ends_at && $userSubscription->ends_at->isPast()) {
                $userSubscription->update([
                    'status' => 'expired',
                    'payment_status' => 'failed',
                ]);
            }
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    protected function handleRecurringRenewal(UserSubscription $userSubscription, string $transactionId, string $orderId): void
    {

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
