<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Lunar\Facades\Payments;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Transaction;

class CheckoutController extends Controller
{
    /**
     * Set shipping and billing addresses for cart.
     */
    public function setAddresses(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.line_one' => ['required', 'string', 'max:255'],
            'shipping_address.line_two' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.postcode' => ['required', 'string', 'max:255'],
            'shipping_address.country_id' => ['required', 'exists:lunar_countries,id'],
            'shipping_address.contact_email' => ['required', 'email', 'max:255'],
            'shipping_address.contact_phone' => ['required', 'string', 'max:255'],
            'billing_same_as_shipping' => ['boolean'],
            'billing_address' => ['required_if:billing_same_as_shipping,false', 'array'],
            'billing_address.first_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.last_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.line_one' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.line_two' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.postcode' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.country_id' => ['required_if:billing_same_as_shipping,false', 'exists:lunar_countries,id'],
            'billing_address.contact_email' => ['nullable', 'email', 'max:255'],
            'billing_address.contact_phone' => ['nullable', 'string', 'max:255'],
        ]);

        // Get or create cart for user
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.',
                'data' => null,
            ], 404);
        }

        // Set shipping address
        $cart->shippingAddress()->updateOrCreate(
            ['cart_id' => $cart->id, 'type' => 'shipping'],
            [
                'first_name' => $validated['shipping_address']['first_name'],
                'last_name' => $validated['shipping_address']['last_name'],
                'line_one' => $validated['shipping_address']['line_one'],
                'line_two' => $validated['shipping_address']['line_two'] ?? null,
                'city' => $validated['shipping_address']['city'],
                'state' => $validated['shipping_address']['state'] ?? null,
                'postcode' => $validated['shipping_address']['postcode'],
                'country_id' => $validated['shipping_address']['country_id'],
                'contact_email' => $validated['shipping_address']['contact_email'],
                'contact_phone' => $validated['shipping_address']['contact_phone'],
            ]
        );

        // Set billing address
        if ($request->boolean('billing_same_as_shipping', true)) {
            $cart->billingAddress()->updateOrCreate(
                ['cart_id' => $cart->id, 'type' => 'billing'],
                [
                    'first_name' => $validated['shipping_address']['first_name'],
                    'last_name' => $validated['shipping_address']['last_name'],
                    'line_one' => $validated['shipping_address']['line_one'],
                    'line_two' => $validated['shipping_address']['line_two'] ?? null,
                    'city' => $validated['shipping_address']['city'],
                    'state' => $validated['shipping_address']['state'] ?? null,
                    'postcode' => $validated['shipping_address']['postcode'],
                    'country_id' => $validated['shipping_address']['country_id'],
                    'contact_email' => $validated['shipping_address']['contact_email'],
                    'contact_phone' => $validated['shipping_address']['contact_phone'],
                ]
            );
        } else {
            $cart->billingAddress()->updateOrCreate(
                ['cart_id' => $cart->id, 'type' => 'billing'],
                [
                    'first_name' => $validated['billing_address']['first_name'],
                    'last_name' => $validated['billing_address']['last_name'],
                    'line_one' => $validated['billing_address']['line_one'],
                    'line_two' => $validated['billing_address']['line_two'] ?? null,
                    'city' => $validated['billing_address']['city'],
                    'state' => $validated['billing_address']['state'] ?? null,
                    'postcode' => $validated['billing_address']['postcode'],
                    'country_id' => $validated['billing_address']['country_id'],
                    'contact_email' => $validated['billing_address']['contact_email'] ?? null,
                    'contact_phone' => $validated['billing_address']['contact_phone'] ?? null,
                ]
            );
        }

        // Recalculate cart with addresses
        $cart->calculate();

        return response()->json([
            'status' => 200,
            'message' => 'Addresses saved successfully.',
            'data' => [
                'cart_id' => $cart->id,
            ],
        ]);
    }

    /**
     * Initiate payment for cart.
     */
    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:senangpay,cash-in-hand'],
        ]);

        // Get user's cart
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart || $cart->lines->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'Cart is empty.',
                'data' => null,
            ], 400);
        }

        // Check if addresses are set
        if (! $cart->shippingAddress || ! $cart->billingAddress) {
            return response()->json([
                'status' => 400,
                'message' => 'Please set shipping and billing addresses first.',
                'data' => null,
            ], 400);
        }

        // Calculate cart and set default shipping option
        $cart->calculate();

        // Set default shipping option (BASDEL - Basic Delivery)
        $shippingOption = ShippingManifest::getOption($cart, 'BASDEL');
        if ($shippingOption) {
            $cart->setShippingOption($shippingOption);
        }

        // Initiate payment based on method
        if ($validated['payment_method'] === 'senangpay') {
            try {
                $paymentDriver = Payments::driver('senangpay')
                    ->cart($cart)
                    ->withData([])
                    ->setConfig(config('lunar.payments.types.senangpay'));

                $response = $paymentDriver->authorize();

                if ($response && $response->success) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Payment initiated successfully.',
                        'data' => [
                            'payment_url' => $response->data['payment_url'],
                            'reference_number' => $response->data['reference_number'],
                            'order_id' => $response->orderId,
                            'amount' => $cart->total->formatted,
                            'currency' => $cart->currency->code,
                        ],
                    ]);
                }

                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to initiate payment.',
                    'data' => null,
                ], 500);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Payment initiation error: '.$e->getMessage(),
                    'data' => null,
                ], 500);
            }
        } else {
            // Cash in hand - create order directly
            $order = $cart->createOrder();

            if ($order) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Order created successfully.',
                    'data' => [
                        'order_id' => $order->id,
                        'reference' => $order->reference,
                        'payment_method' => 'cash-in-hand',
                    ],
                ]);
            }

            return response()->json([
                'status' => 500,
                'message' => 'Failed to create order.',
                'data' => null,
            ], 500);
        }
    }

    /**
     * Check payment status by reference number.
     */
    public function checkPaymentStatus(string $reference)
    {
        // Find order by reference (check both senangpay and revpay for backward compatibility)
        $order = Order::where(function ($query) use ($reference) {
            $query->whereJsonContains('meta->senangpay_reference', $reference)
                  ->orWhereJsonContains('meta->revpay_reference', $reference);
        })->first();

        if (! $order) {
            return response()->json([
                'status' => 404,
                'message' => 'Order not found.',
                'data' => null,
            ], 404);
        }

        // Get latest transaction (check both senangpay and revpay)
        $transaction = Transaction::where('reference', $reference)
            ->whereIn('driver', ['senangpay', 'revpay'])
            ->latest()
            ->first();

        // Determine payment status
        $paymentStatus = 'pending';
        if ($transaction) {
            if ($transaction->type === 'capture' && $transaction->success) {
                $paymentStatus = 'success';
            } elseif ($order->status === 'payment-failed') {
                $paymentStatus = 'failed';
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Payment status retrieved.',
            'data' => [
                'reference_number' => $reference,
                'payment_status' => $paymentStatus,
                'order_id' => $order->id,
                'order_status' => $order->status,
                'transaction_id' => $transaction?->meta['transaction_id'] ?? null,
                'amount' => $order->total->formatted,
                'currency' => $order->currency->code,
            ],
        ]);
    }

    /**
     * Get user's order history.
     */
    public function orderHistory(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['currency', 'lines'])
            ->latest()
            ->get();

        // Load purchasable relationship only for ProductVariant lines to avoid morphTo issues with ShippingOption
        $orders->each(function ($order) {
            $order->lines->each(function ($line) {
                if ($line->purchasable_type === 'Lunar\Models\ProductVariant') {
                    $line->load(['purchasable.product.productType', 'purchasable.product.thumbnail']);
                }
            });
        });

        return OrderResource::collection($orders)
            ->additional([
                'status' => 200,
                'message' => 'Orders retrieved successfully.',
            ]);
    }

    /**
     * Get specific order details.
     */
    public function orderDetail(Request $request, Order $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized.',
                'data' => null,
            ], 403);
        }

        $order->load([
            'currency',
            'lines',
            'shippingAddress.country',
            'billingAddress.country',
            'transactions',
        ]);

        // Load purchasable relationship only for ProductVariant lines to avoid morphTo issues with ShippingOption
        $order->lines->each(function ($line) {
            if ($line->purchasable_type === 'Lunar\Models\ProductVariant') {
                $line->load(['purchasable.product.productType', 'purchasable.product.thumbnail']);
            }
        });

        return OrderResource::make($order)
            ->additional([
                'status' => 200,
                'message' => 'Order retrieved successfully.',
            ]);
    }
}
