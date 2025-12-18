<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Lunar\Models\Order;

class OrderHistoryController extends Controller
{
    /**
     * Display a listing of the user's orders.
     */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with([
                'lines.purchasable.product.productType',
                'currency',
            ])
            ->latest()
            ->get();

        return Inertia::render('order-history', [
            'orders' => $orders,
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load([
            'lines.purchasable.product.productType',
            'lines.purchasable.product.thumbnail',
            'currency',
            'shippingAddress.country',
            'billingAddress.country',
        ]);

        return Inertia::render('order-history/show', [
            'order' => $order,
        ]);
    }
}
