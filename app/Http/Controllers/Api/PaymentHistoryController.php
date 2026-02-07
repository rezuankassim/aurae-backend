<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentHistoryResource;
use Illuminate\Http\Request;
use Lunar\Models\Order;

class PaymentHistoryController extends Controller
{
    /**
     * Get user's payment history.
     *
     * This includes:
     * - Marketplace orders (from Lunar)
     * - Subscription fees (to be implemented)
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

        // TODO: Add subscription fee transactions here when subscription module is implemented
        // Example structure:
        // $subscriptions = UserSubscriptionTransaction::where('user_id', $user->id)
        //     ->latest()
        //     ->get();
        // $subscriptionHistory = $subscriptions->map(function ($subscription) {
        //     return [
        //         'type' => 'subscription',
        //         'data' => $subscription,
        //     ];
        // });
        // $paymentHistory = $paymentHistory->merge($subscriptionHistory);

        // Sort by date (newest first)
        $paymentHistory = $paymentHistory->sortByDesc(function ($item) {
            if ($item['type'] === 'marketplace') {
                return $item['data']->placed_at ?? $item['data']->created_at;
            }

            // Add sorting for subscription when implemented
            return null;
        })->values();

        return PaymentHistoryResource::collection($paymentHistory)
            ->additional([
                'status' => 200,
                'message' => 'Payment history retrieved successfully.',
            ]);
    }
}
