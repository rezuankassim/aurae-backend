<?php

namespace App\Http\Controllers\Payment;

use App\Events\PaymentCompleted;
use App\Http\Controllers\Controller;
use App\Services\SenangpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Payments;
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
}
