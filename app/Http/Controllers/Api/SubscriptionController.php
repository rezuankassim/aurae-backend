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
     * Get user's active subscription.
     */
    public function userSubscription(Request $request)
    {
        $user = $request->user();
        $activeSubscription = $user->activeSubscription()->with('subscription')->first();

        if (! $activeSubscription) {
            return BaseResource::make(null)
                ->additional([
                    'status' => 200,
                    'message' => 'No active subscription found.',
                ]);
        }

        return UserSubscriptionResource::make($activeSubscription)
            ->additional([
                'status' => 200,
                'message' => 'User subscription retrieved successfully.',
            ]);
    }
}
