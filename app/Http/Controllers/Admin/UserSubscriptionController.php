<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserSubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = UserSubscription::with(['user', 'subscription']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Payment status filter
        if ($request->has('payment_status') && $request->payment_status !== '') {
            $query->where('payment_status', $request->payment_status);
        }

        $userSubscriptions = $query->latest()->paginate(20);

        return Inertia::render('admin/user-subscription/index', [
            'userSubscriptions' => $userSubscriptions,
            'filters' => $request->only(['search', 'status', 'payment_status']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserSubscription $userSubscription)
    {
        $userSubscription->load(['user', 'subscription', 'user.machines']);

        return Inertia::render('admin/user-subscription/show', [
            'userSubscription' => $userSubscription,
        ]);
    }

    /**
     * Cancel user subscription.
     */
    public function cancel(UserSubscription $userSubscription)
    {
        if ($userSubscription->status === 'cancelled') {
            return back()->with('error', 'Subscription is already cancelled.');
        }

        $userSubscription->update([
            'status' => 'cancelled',
            'ends_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled successfully.');
    }

    /**
     * Extend user subscription.
     */
    public function extend(Request $request, UserSubscription $userSubscription)
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $currentEndDate = $userSubscription->ends_at ?? now();
        $newEndDate = $currentEndDate->addMonths($validated['months']);

        $userSubscription->update([
            'ends_at' => $newEndDate,
            'status' => 'active',
        ]);

        return back()->with('success', "Subscription extended by {$validated['months']} month(s).");
    }
}
