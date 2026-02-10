<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentHistoryResource;
use App\Models\SubscriptionTransaction;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Lunar\Models\Order;

class PaymentHistoryController extends Controller
{
    /**
     * Get user's payment history.
     *
     * This includes:
     * - Marketplace orders (from Lunar)
     * - Subscription payments (from SubscriptionTransaction)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get marketplace orders
        $orders = Order::where('user_id', $user->id)
            ->with(['currency'])
            ->latest('placed_at')
            ->get();

        // Transform orders into payment history items
        $paymentHistory = $orders->map(function ($order) {
            return [
                'type' => 'marketplace',
                'data' => $order,
            ];
        });

        // Get user's subscription IDs
        $userSubscriptionIds = UserSubscription::where('user_id', $user->id)
            ->pluck('id');

        // Get subscription transactions (only captured/successful payments)
        $subscriptionTransactions = SubscriptionTransaction::whereIn('user_subscription_id', $userSubscriptionIds)
            ->where('type', 'capture')
            ->where('success', true)
            ->with(['userSubscription.subscription'])
            ->latest('captured_at')
            ->get();

        // Transform subscription transactions into payment history items
        $subscriptionHistory = $subscriptionTransactions->map(function ($transaction) {
            return [
                'type' => 'subscription',
                'data' => $transaction,
            ];
        });

        // Merge all payment history
        $paymentHistory = $paymentHistory->merge($subscriptionHistory);

        // Sort by date (newest first)
        $paymentHistory = $paymentHistory->sortByDesc(function ($item) {
            if ($item['type'] === 'marketplace') {
                return $item['data']->placed_at ?? $item['data']->created_at;
            }

            if ($item['type'] === 'subscription') {
                return $item['data']->captured_at ?? $item['data']->created_at;
            }

            return null;
        })->values();

        return PaymentHistoryResource::collection($paymentHistory)
            ->additional([
                'status' => 200,
                'message' => 'Payment history retrieved successfully.',
            ]);
    }
}
