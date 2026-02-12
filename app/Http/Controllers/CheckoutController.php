<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Facades\CartSession;
use Lunar\Models\Country;
use Lunar\Models\Order;
use Lunar\Shipping\DataTransferObjects\ShippingOptionLookup;
use Lunar\Shipping\Facades\Shipping;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page.
     */
    public function index()
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $cart->calculate();
        $cart->load([
            'lines.purchasable.product.productType',
            'lines.purchasable.product.thumbnail',
            'currency',
            'shippingAddress',
            'billingAddress',
        ]);

        $countries = Country::get();

        return Inertia::render('checkout/index', [
            'cart' => $cart,
            'countries' => $countries,
        ]);
    }

    /**
     * Save shipping and billing addresses.
     */
    public function saveAddress(Request $request)
    {
        $validated = $request->validate([
            'shipping_first_name' => ['required', 'string', 'max:255'],
            'shipping_last_name' => ['required', 'string', 'max:255'],
            'shipping_line_one' => ['required', 'string', 'max:255'],
            'shipping_line_two' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:255'],
            'shipping_state' => ['nullable', 'string', 'max:255'],
            'shipping_postcode' => ['required', 'string', 'max:255'],
            'shipping_country_id' => ['required', 'exists:lunar_countries,id'],
            'shipping_contact_email' => ['required', 'email', 'max:255'],
            'shipping_contact_phone' => ['required', 'string', 'max:255'],
            'same_as_shipping' => ['boolean'],
            'billing_first_name' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_last_name' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_line_one' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_line_two' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_state' => ['nullable', 'string', 'max:255'],
            'billing_postcode' => ['required_if:same_as_shipping,false', 'string', 'max:255'],
            'billing_country_id' => ['required_if:same_as_shipping,false', 'exists:lunar_countries,id'],
            'billing_contact_email' => ['nullable', 'email', 'max:255'],
            'billing_contact_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $cart = CartSession::current();

        if (! $cart) {
            return redirect()->route('cart.index')
                ->with('error', 'Cart not found.');
        }

        // Set shipping address
        $cart->shippingAddress()->updateOrCreate(
            ['cart_id' => $cart->id, 'type' => 'shipping'],
            [
                'first_name' => $validated['shipping_first_name'],
                'last_name' => $validated['shipping_last_name'],
                'line_one' => $validated['shipping_line_one'],
                'line_two' => $validated['shipping_line_two'] ?? null,
                'city' => $validated['shipping_city'],
                'state' => $validated['shipping_state'] ?? null,
                'postcode' => $validated['shipping_postcode'],
                'country_id' => $validated['shipping_country_id'],
                'contact_email' => $validated['shipping_contact_email'],
                'contact_phone' => $validated['shipping_contact_phone'],
            ]
        );

        // Set billing address
        if ($request->boolean('same_as_shipping')) {
            $cart->billingAddress()->updateOrCreate(
                ['cart_id' => $cart->id, 'type' => 'billing'],
                [
                    'first_name' => $validated['shipping_first_name'],
                    'last_name' => $validated['shipping_last_name'],
                    'line_one' => $validated['shipping_line_one'],
                    'line_two' => $validated['shipping_line_two'] ?? null,
                    'city' => $validated['shipping_city'],
                    'state' => $validated['shipping_state'] ?? null,
                    'postcode' => $validated['shipping_postcode'],
                    'country_id' => $validated['shipping_country_id'],
                    'contact_email' => $validated['shipping_contact_email'],
                    'contact_phone' => $validated['shipping_contact_phone'],
                ]
            );
        } else {
            $cart->billingAddress()->updateOrCreate(
                ['cart_id' => $cart->id, 'type' => 'billing'],
                [
                    'first_name' => $validated['billing_first_name'],
                    'last_name' => $validated['billing_last_name'],
                    'line_one' => $validated['billing_line_one'],
                    'line_two' => $validated['billing_line_two'] ?? null,
                    'city' => $validated['billing_city'],
                    'state' => $validated['billing_state'] ?? null,
                    'postcode' => $validated['billing_postcode'],
                    'country_id' => $validated['billing_country_id'],
                    'contact_email' => $validated['billing_contact_email'] ?? null,
                    'contact_phone' => $validated['billing_contact_phone'] ?? null,
                ]
            );
        }

        return redirect()->route('checkout.review');
    }

    /**
     * Show order review page.
     */
    public function review()
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        if (! $cart->shippingAddress || ! $cart->billingAddress) {
            return redirect()->route('checkout.index')
                ->with('error', 'Please complete your shipping information.');
        }

        $cart->calculate();
        $cart->load([
            'lines.purchasable.product.productType',
            'lines.purchasable.product.thumbnail',
            'currency',
            'shippingAddress.country',
            'billingAddress.country',
        ]);

        return Inertia::render('checkout/review', [
            'cart' => $cart,
        ]);
    }

    /**
     * Complete the order.
     */
    public function complete(Request $request)
    {
        $cart = CartSession::current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        if (! $cart->shippingAddress || ! $cart->billingAddress) {
            return redirect()->route('checkout.index')
                ->with('error', 'Please complete your shipping information.');
        }

        // Calculate cart and set default shipping option
        $cart->calculate();

        // Set default shipping option (BASDEL - Basic Delivery)
        $shippingRates = Shipping::shippingRates($cart)->get();
        $shippingOptions = Shipping::shippingOptions($cart)->get(
            new ShippingOptionLookup(
                shippingRates: $shippingRates
            )
        );

        // Find BASDEL option
        foreach ($shippingOptions as $optionResult) {
            if ($optionResult->option->getIdentifier() === 'BASDEL') {
                $cart->setShippingOption($optionResult->option);
                break;
            }
        }

        // Create the order
        $order = $cart->createOrder(
            allowMultipleOrders: false,
        );

        if ($order) {
            // Clear the cart session
            CartSession::forget();

            return redirect()->route('checkout.success', ['order' => $order->id])
                ->with('success', 'Order placed successfully!');
        }

        return back()->with('error', 'Failed to create order. Please try again.');
    }

    /**
     * Show order success page.
     */
    public function success(Order $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load([
            'lines.purchasable.product',
            'currency',
            'shippingAddress',
            'billingAddress',
        ]);

        return Inertia::render('checkout/success', [
            'order' => $order,
        ]);
    }
}
