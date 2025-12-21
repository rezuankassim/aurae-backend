<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\CollectionResource;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lunar\Models\Cart;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;

class EcommerceController extends Controller
{
    public function collections(Request $request)
    {
        $collections = Collection::query()
            ->with([
                'thumbnail', 
                'products.media',
                'products.variants.basePrices.currency', 
                'products.variants.basePrices.priceable',
                'products.variants.values.option',
                'products.defaultUrl'
            ])
            ->get();

        return CollectionResource::collection($collections)
            ->additional([
                'status' => 200,
                'message' => 'Collections retrieved successfully.',
            ]);
    }

    public function cart(Request $request)
    {
        $cart = Cart::with(['lines.purchasable'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'currency_id' => Currency::getDefault()->id,
                'channel_id' => Channel::getDefault()->id,
            ]);
        }

        $cart = $cart->recalculate();

        return CartResource::make($cart)
            ->additional([
                'status' => 200,
                'message' => 'Cart retrieved successfully.',
            ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_variant_id' => ['required', 'exists:lunar_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $cart = Cart::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'currency_id' => Currency::getDefault()->id,
                'channel_id' => Channel::getDefault()->id,
            ]
        );
        
        $productVariant = ProductVariant::findOrFail($request->product_variant_id);

        $cart->add($productVariant, $request->quantity);

        return CartResource::make($cart)
            ->additional([
                'status' => 200,
                'message' => 'Cart line added successfully.',
            ]);
    }

    public function removeFromCart(Request $request)
    {
        $request->validate([
            'cart_line_id' => ['required', 'exists:lunar_cart_lines,id'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        $cart->remove($request->cart_line_id);

        return CartResource::make($cart)
            ->additional([
                'status' => 200,
                'message' => 'Cart line removed successfully.',
            ]);
    }
}
