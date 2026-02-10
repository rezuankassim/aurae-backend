<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\Subscription;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use App\Services\SenangpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        protected SenangpaySignatureService $signatureService
    ) {}

    /**
     * Initiate recurring subscription payment.
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

        // Check if subscription has recurring_id configured
        if (! $subscription->senangpay_recurring_id) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 400,
                    'message' => 'This subscription plan is not configured for recurring payments.',
                ])
                ->response()
                ->setStatusCode(400);
        }

        // Check if user already has an active subscription
        $existingSubscription = UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('cancelled_at')
            ->first();

        if ($existingSubscription) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 400,
                    'message' => 'You already have an active subscription.',
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
                'ends_at' => now()->addMonth(),
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'is_recurring' => true,
                'next_billing_at' => now()->addMonth(),
            ]);

            // Generate reference number
            $referenceNumber = 'SUB-'.date('Y').'-'.str_pad($userSubscription->id, 5, '0', STR_PAD_LEFT);

            // Get SenangPay config
            $merchantId = config('services.senangpay.merchant_id');
            $secretKey = config('services.senangpay.secret_key');
            $recurringBaseUrl = config('services.senangpay.recurring_base_url', 'https://api.senangpay.my');

            // Get customer details
            $customerName = $user->name ?? 'Customer';
            $customerEmail = $user->email ?? '';
            $customerPhone = $user->phone ?? '';

            // Generate hash for recurring payment: hash('sha256', secret_key + order_id + recurring_id)
            $hash = $this->signatureService->generateRecurringPaymentHash(
                $secretKey,
                $referenceNumber,
                $subscription->senangpay_recurring_id
            );

            // Create transaction record
            SubscriptionTransaction::create([
                'user_subscription_id' => $userSubscription->id,
                'success' => true,
                'type' => 'intent',
                'driver' => 'senangpay',
                'amount' => $subscription->price,
                'reference' => $referenceNumber,
                'status' => 'pending',
                'notes' => 'Recurring subscription payment intent created',
                'meta' => [
                    'subscription_id' => $subscription->id,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'recurring_id' => $subscription->senangpay_recurring_id,
                    'type' => 'recurring_subscription',
                ],
            ]);

            // Update user subscription with transaction reference
            $userSubscription->update([
                'transaction_id' => $referenceNumber,
            ]);

            // Build recurring payment URL
            $paymentUrl = $recurringBaseUrl.'/recurring/payment/'.$merchantId.'?'.http_build_query([
                'order_id' => $referenceNumber,
                'recurring_id' => $subscription->senangpay_recurring_id,
                'hash' => $hash,
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
            ]);

            Log::info('Recurring subscription payment initiated', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'reference' => $referenceNumber,
                'recurring_id' => $subscription->senangpay_recurring_id,
            ]);

            return BaseResource::make([
                'payment_url' => $paymentUrl,
                'reference_number' => $referenceNumber,
                'subscription' => $subscription,
                'amount' => 'RM '.number_format($subscription->price, 2),
                'currency' => 'MYR',
                'is_recurring' => true,
            ])
                ->additional([
                    'status' => 200,
                    'message' => 'Recurring subscription payment initiated successfully.',
                ]);
        } catch (\Exception $e) {
            Log::error('Recurring subscription payment initiation failed', [
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
        $transaction = SubscriptionTransaction::where('reference', $reference)
            ->where('driver', 'senangpay')
            ->where('type', 'intent')
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

        $userSubscription = UserSubscription::with('subscription')->find($transaction->user_subscription_id);

        if (! $userSubscription) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 404,
                    'message' => 'User subscription not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        // Determine payment status
        $paymentStatus = 'pending';
        $captureTransaction = SubscriptionTransaction::where('reference', $reference)
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
