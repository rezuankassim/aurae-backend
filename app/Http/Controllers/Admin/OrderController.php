<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Models\Order;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders.
     */
    public function index()
    {
        $orders = Order::with([
            'lines.purchasable.product.productType',
            'currency',
            'user',
        ])
            ->latest()
            ->get();

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $order->load([
            'lines.purchasable.product.productType',
            'lines.purchasable.product.thumbnail',
            'currency',
            'user',
            'shippingAddress.country',
            'billingAddress.country',
        ]);

        return Inertia::render('admin/orders/show', [
            'order' => $order,
        ]);
    }

    /**
     * Update the order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:awaiting-payment,payment-offline,payment-received,dispatched'],
        ]);

        $order->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Order status updated successfully.');
    }
}
