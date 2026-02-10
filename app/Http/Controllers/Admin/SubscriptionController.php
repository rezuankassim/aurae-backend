<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscriptions = Subscription::latest()->get();

        return Inertia::render('admin/subscription/index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/subscription/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'pricing_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'icon' => 'nullable|image|max:2048',
            'senangpay_recurring_id' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('subscriptions', 'public');
            $validated['icon'] = $path;
        }

        Subscription::create($validated);

        return to_route('admin.subscription.index')->with('success', 'Subscription created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        transform($subscription, function ($item) {
            $item->icon_url = $item->icon ? asset('storage/'.$item->icon) : null;

            return $item;
        });

        return Inertia::render('admin/subscription/edit', [
            'subscription' => $subscription,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'pricing_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'icon' => 'nullable|image|max:2048',
            'senangpay_recurring_id' => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('subscriptions', 'public');
            $validated['icon'] = $path;
        }

        $subscription->update($validated);

        return to_route('admin.subscription.index')->with('success', 'Subscription updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return to_route('admin.subscription.index')->with('success', 'Subscription deleted successfully');
    }
}
