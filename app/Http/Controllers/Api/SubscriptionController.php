<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\UserSubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Get all active subscriptions.
     */
    public function index()
    {
        $subscriptions = Subscription::active()->get();

        return SubscriptionResource::collection($subscriptions)
            ->additional([
                'status' => 200,
                'message' => 'Subscriptions retrieved successfully.',
            ]);
    }

    /**
     * Get user's active subscriptions.
     */
    public function userSubscription(Request $request)
    {
        $user = $request->user();
        $activeSubscriptions = $user->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->with('subscription')
            ->latest()
            ->get();

        if ($activeSubscriptions->isEmpty()) {
            return BaseResource::make([])
                ->additional([
                    'status' => 200,
                    'message' => 'No active subscriptions found.',
                ]);
        }

        return UserSubscriptionResource::collection($activeSubscriptions)
            ->additional([
                'status' => 200,
                'message' => 'User subscriptions retrieved successfully.',
            ]);
    }
}
