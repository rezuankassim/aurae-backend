<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;

class CartController extends Controller
{
    /**
     * Display the cart.
     */
    public function index()
    {
        $cart = CartSession::current();

        if ($cart) {
            $cart->calculate();
            $cart->load([
                'lines.purchasable.product.productType',
                'lines.purchasable.product.thumbnail',
                'lines.purchasable.values.option',
                'currency',
            ]);
        }

        return Inertia::render('cart/index', [
            'cart' => $cart,
        ]);
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => ['required', 'exists:lunar_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $variant = ProductVariant::find($request->variant_id);

        $cart = CartSession::current();

        if (! $cart) {
            $cart = Cart::create([
                'currency_id' => Currency::getDefault()->id,
                'user_id' => auth()->id(),
                'channel_id' => Channel::getDefault()->id,
            ]);
            CartSession::use($cart);
        }

        $cart->add(
            purchasable: $variant,
            quantity: $request->quantity,
        );

        return back()->with('success', 'Product added to cart successfully.');
    }

    /**
     * Update cart line quantity.
     */
    public function updateLine(Request $request, CartLine $cartLine)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartLine->update([
            'quantity' => $request->quantity,
        ]);

        $cartLine->cart->calculate();

        return back()->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove item from cart.
     */
    public function removeLine(CartLine $cartLine)
    {
        $cartLine->delete();

        $cartLine->cart->calculate();

        return back()->with('success', 'Item removed from cart.');
    }
}
