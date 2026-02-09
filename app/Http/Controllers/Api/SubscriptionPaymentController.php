<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\Subscription;
use App\Models\UserSubscription;
use App\Services\SenangpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Transaction;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        protected SenangpaySignatureService $signatureService
    ) {}

    /**
     * Initiate subscription payment.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'payment_method' => ['required', 'string', 'in:senangpay'],
        ]);

        $user = $request->user();

        $subscription = Subscription::findOrFail($validated['subscription_id']);

        if (! $subscription->is_active) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 400,
                    'message' => 'This subscription plan is not available.',
                ])
                ->response()
                ->setStatusCode(400);
        }

        try {
            // Create pending user subscription
            $userSubscription = UserSubscription::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(), // Default to 1 month
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
            ]);

            // Generate reference number
            $referenceNumber = 'SUB-'.date('Y').'-'.str_pad($userSubscription->id, 5, '0', STR_PAD_LEFT);

            // Get SenangPay config
            $merchantId = config('services.senangpay.merchant_id');
            $secretKey = config('services.senangpay.secret_key');
            $baseUrl = config('services.senangpay.base_url', 'https://app.senangpay.my');

            // Format amount to decimal
            $amount = $this->signatureService->formatAmount($subscription->price * 100); // Convert to cents

            // Get customer details
            $customerName = $user->name ?? 'Customer';
            $customerEmail = $user->email ?? '';
            $customerPhone = $user->phone ?? '';

            // Build detail description
            $detail = 'Subscription_'.$subscription->title.'_'.$referenceNumber;

            // Generate hash
            $hash = $this->signatureService->generatePaymentHash(
                $secretKey,
                $detail,
                $amount,
                $referenceNumber
            );

            // Create transaction record
            Transaction::create([
                'order_id' => null, // No order for subscriptions
                'success' => true,
                'type' => 'intent',
                'driver' => 'senangpay',
                'amount' => $subscription->price,
                'reference' => $referenceNumber,
                'status' => 'pending',
                'card_type' => '',
                'last_four' => '',
                'notes' => 'Subscription payment intent created',
                'meta' => [
                    'subscription_id' => $subscription->id,
                    'user_subscription_id' => $userSubscription->id,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'amount' => $amount,
                    'detail' => $detail,
                    'type' => 'subscription',
                ],
            ]);

            // Update user subscription with transaction reference
            $userSubscription->update([
                'transaction_id' => $referenceNumber,
            ]);

            // Build payment URL
            $paymentUrl = $baseUrl.'/payment/'.$merchantId.'?'.http_build_query([
                'detail' => $detail,
                'amount' => $amount,
                'order_id' => $referenceNumber,
                'hash' => $hash,
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
            ]);

            Log::info('Subscription payment initiated', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'reference' => $referenceNumber,
                'amount' => $amount,
            ]);

            return BaseResource::make([
                'payment_url' => $paymentUrl,
                'reference_number' => $referenceNumber,
                'subscription' => $subscription,
                'amount' => 'RM '.number_format($subscription->price, 2),
                'currency' => 'MYR',
            ])
                ->additional([
                    'status' => 200,
                    'message' => 'Subscription payment initiated successfully.',
                ]);
        } catch (\Exception $e) {
            Log::error('Subscription payment initiation failed', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return BaseResource::make(null)
                ->additional([
                    'status' => 500,
                    'message' => 'Failed to initiate subscription payment: '.$e->getMessage(),
                ])
                ->response()
                ->setStatusCode(500);
        }
    }

    /**
     * Check subscription payment status.
     */
    public function checkPaymentStatus(Request $request, string $reference)
    {
        // Find transaction
        $transaction = Transaction::where('reference', $reference)
            ->where('driver', 'senangpay')
            ->whereJsonContains('meta->type', 'subscription')
            ->latest()
            ->first();

        if (! $transaction) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'Subscription payment not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        // Get user subscription
        $userSubscriptionId = $transaction->meta['user_subscription_id'] ?? null;
        if (! $userSubscriptionId) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'User subscription not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        $userSubscription = UserSubscription::with('subscription')->find($userSubscriptionId);

        // Determine payment status
        $paymentStatus = 'pending';
        $captureTransaction = Transaction::where('reference', $reference)
            ->where('type', 'capture')
            ->where('driver', 'senangpay')
            ->first();

        if ($captureTransaction && $captureTransaction->success) {
            $paymentStatus = 'success';
        } elseif ($userSubscription->status === 'cancelled' || $userSubscription->payment_status === 'failed') {
            $paymentStatus = 'failed';
        }

        return BaseResource::make([
            'reference_number' => $reference,
            'payment_status' => $paymentStatus,
            'subscription' => $userSubscription->subscription,
            'user_subscription_id' => $userSubscription->id,
            'status' => $userSubscription->status,
            'starts_at' => $userSubscription->starts_at,
            'ends_at' => $userSubscription->ends_at,
        ])
            ->additional([
                'status' => 200,
                'message' => 'Subscription payment status retrieved.',
            ]);
    }
}
