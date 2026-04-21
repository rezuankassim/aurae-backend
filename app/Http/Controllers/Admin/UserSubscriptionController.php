<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Show the form for creating a new B2B subscription.
     */
    public function create()
    {
        $subscriptions = Subscription::active()->orderBy('title')->get(['id', 'title', 'pricing_title', 'price']);
        $users = User::where('is_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        return Inertia::render('admin/user-subscription/create', [
            'subscriptions' => $subscriptions,
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created B2B subscription for a single user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'starts_at' => ['required', 'date'],
            'months' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $startsAt = now()->parse($validated['starts_at'])->startOfDay();
        $endsAt = $startsAt->copy()->addMonths($validated['months']);

        UserSubscription::create([
            'user_id' => $validated['user_id'],
            'subscription_id' => $validated['subscription_id'],
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'payment_method' => 'b2b',
            'payment_status' => 'completed',
            'paid_at' => now(),
            'is_recurring' => false,
            'next_billing_at' => null,
        ]);

        return to_route('admin.user-subscriptions.index')->with('success', 'B2B subscription created successfully.');
    }

    /**
     * Bulk-create B2B subscriptions for multiple users.
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'starts_at' => ['required', 'date'],
            'months' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        $startsAt = now()->parse($validated['starts_at'])->startOfDay();
        $endsAt = $startsAt->copy()->addMonths($validated['months']);
        $now = now();

        DB::transaction(function () use ($validated, $startsAt, $endsAt, $now) {
            foreach ($validated['user_ids'] as $userId) {
                UserSubscription::create([
                    'user_id' => $userId,
                    'subscription_id' => $validated['subscription_id'],
                    'status' => 'active',
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'payment_method' => 'b2b',
                    'payment_status' => 'completed',
                    'paid_at' => $now,
                    'is_recurring' => false,
                    'next_billing_at' => null,
                ]);
            }
        });

        $count = count($validated['user_ids']);

        return to_route('admin.user-subscriptions.index')->with('success', "B2B subscriptions created successfully for {$count} user(s).");
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
            'cancelled_at' => now(),
            'ends_at' => now(),
        ]);

        // Check if this is a recurring subscription - admin needs to cancel in SenangPay
        if ($userSubscription->is_recurring) {
            return back()->with([
                'success' => 'Subscription cancelled in the system.',
                'warning' => 'IMPORTANT: This is a recurring subscription. Please also cancel it manually in the SenangPay dashboard to stop future charges.',
            ]);
        }

        return back()->with('success', 'Subscription cancelled successfully.');
    }

    /**
     * Manually activate a user subscription.
     * Detaches from SenangPay by setting payment_method to 'manual' and disabling recurring.
     */
    public function activate(UserSubscription $userSubscription)
    {
        if ($userSubscription->status === 'active') {
            return back()->with('error', 'Subscription is already active.');
        }

        $startsAt = $userSubscription->starts_at ?? now();
        $endsAt = $userSubscription->ends_at && $userSubscription->ends_at->isFuture()
            ? $userSubscription->ends_at
            : $startsAt->copy()->addMonth();

        $userSubscription->update([
            'status'          => 'active',
            'payment_status'  => 'completed',
            'payment_method'  => 'manual',
            'paid_at'         => now(),
            'starts_at'       => $startsAt,
            'ends_at'         => $endsAt,
            'is_recurring'    => false,
            'next_billing_at' => null,
            'cancelled_at'    => null,
        ]);

        return back()->with('success', 'Subscription activated manually. Payment method set to manual and recurring billing disabled.');
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
