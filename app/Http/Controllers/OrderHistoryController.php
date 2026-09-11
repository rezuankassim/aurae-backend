<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Lunar\Models\Order;

class OrderHistoryController extends Controller
{
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

    public function show(Order $order)
    {

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
