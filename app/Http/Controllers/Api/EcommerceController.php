<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartLineResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\CollectionResource;
use Illuminate\Http\Request;
use Lunar\Exceptions\Carts\CartException;
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
                'products' => fn ($q) => $q->where('status', 'published'),
                'products.media',
                'products.variants.basePrices.currency',
                'products.variants.basePrices.priceable',
                'products.variants.values.option',
                'products.defaultUrl',
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
        $cart = Cart::with([
            'lines.purchasable.values.option',
            'lines.purchasable.images',
            'lines.purchasable.basePrices',
            'lines.purchasable.product.variants.values.option',
            'lines.purchasable.product.variants.basePrices',
            'lines.purchasable.product.variants.images',
            'lines.purchasable.product.media',
            'lines.purchasable.product.thumbnail',
        ])
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $cart) {
            $cart = Cart::create([
                'user_id' => $request->user()->id,
                'customer_id' => $request->user()->getOrCreateCustomer()->id,
                'currency_id' => Currency::getDefault()->id,
                'channel_id' => Channel::getDefault()->id,
            ]);
        } else {
            // Remove any cart lines whose product is no longer published (active)
            // or whose variant can no longer fulfill the requested quantity (out of stock)
            $staleLineIds = $cart->lines
                ->filter(fn ($line) => $line->purchasable?->product?->status !== 'published'
                    || ! $line->purchasable?->canBeFulfilledAtQuantity($line->quantity)
                )
                ->pluck('id');

            if ($staleLineIds->isNotEmpty()) {
                $cart->lines()->whereIn('id', $staleLineIds)->delete();
                $cart->unsetRelation('lines');
            }
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
                'customer_id' => $request->user()->getOrCreateCustomer()->id,
                'currency_id' => Currency::getDefault()->id,
                'channel_id' => Channel::getDefault()->id,
            ]
        );

        $productVariant = ProductVariant::findOrFail($request->product_variant_id);

        try {
            $cart->add($productVariant, $request->quantity);
        } catch (CartException $e) {
            return response()->json([
                'status' => 422,
                'message' => $e->errors()->first(),
                'data' => null,
            ], 422);
        }

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

    public function updateCartLineQuantity(Request $request)
    {
        $request->validate([
            'cart_line_id' => ['required', 'exists:lunar_cart_lines,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        $line = $cart->lines()->where('id', $request->cart_line_id)->first();

        if (! $line) {
            return response()->json([
                'status' => 422,
                'message' => 'Cart line does not belong to your cart.',
                'data' => null,
            ], 422);
        }

        try {
            $cart = $cart->updateLine($request->cart_line_id, $request->quantity);
        } catch (CartException $e) {
            return response()->json([
                'status' => 422,
                'message' => $e->errors()->first(),
                'data' => null,
            ], 422);
        }

        return CartResource::make($cart)
            ->additional([
                'status' => 200,
                'message' => 'Cart line quantity updated successfully.',
            ]);
    }

    public function swapCartLineVariant(Request $request)
    {
        $request->validate([
            'cart_line_id' => ['required', 'exists:lunar_cart_lines,id'],
            'product_variant_id' => ['required', 'exists:lunar_product_variants,id'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        $line = $cart->lines()->with('purchasable')->where('id', $request->cart_line_id)->first();

        if (! $line) {
            return response()->json([
                'status' => 422,
                'message' => 'Cart line does not belong to your cart.',
                'data' => null,
            ], 422);
        }

        $newVariant = ProductVariant::with('product')->findOrFail($request->product_variant_id);

        // Ensure the new variant belongs to the same product
        if ($line->purchasable->product_id !== $newVariant->product_id) {
            return response()->json([
                'status' => 422,
                'message' => 'The selected variant does not belong to the same product.',
                'data' => null,
            ], 422);
        }

        // If the same variant is selected, nothing to do
        if ($line->purchasable_id === $newVariant->id) {
            $cart = $cart->recalculate();

            $updatedLine = $cart->lines->first(fn ($l) => $l->purchasable_id === $newVariant->id && $l->purchasable_type === ProductVariant::modelClass());

            $updatedLine->load([
                'purchasable.values.option',
                'purchasable.images',
                'purchasable.basePrices',
                'purchasable.product.variants.values.option',
                'purchasable.product.variants.basePrices',
                'purchasable.product.variants.images',
                'purchasable.product.media',
                'purchasable.product.thumbnail',
            ]);

            return CartLineResource::make($updatedLine)
                ->additional([
                    'status' => 200,
                    'message' => 'Cart updated successfully.',
                ]);
        }

        $quantity = $line->quantity;

        try {
            $cart->remove($line->id, refresh: false);
            $cart = $cart->add($newVariant, $quantity);
        } catch (CartException $e) {
            return response()->json([
                'status' => 422,
                'message' => $e->errors()->first(),
                'data' => null,
            ], 422);
        }

        $cart->refresh();

        $newLine = $cart->lines->first(fn ($l) => $l->purchasable_id === $newVariant->id && $l->purchasable_type === ProductVariant::modelClass());

        $newLine->load([
            'purchasable.values.option',
            'purchasable.images',
            'purchasable.basePrices',
            'purchasable.product.variants.values.option',
            'purchasable.product.variants.basePrices',
            'purchasable.product.variants.images',
            'purchasable.product.media',
            'purchasable.product.thumbnail',
        ]);

        return CartLineResource::make($newLine)
            ->additional([
                'status' => 200,
                'message' => 'Cart variant swapped successfully.',
            ]);
    }

    /**
     * Set which cart lines are selected for checkout.
     *
     * The provided line_ids become selected=true; all other lines in the cart
     * become selected=false. Passing an empty array deselects everything.
     */
    public function selectLines(Request $request)
    {
        $request->validate([
            'line_ids' => ['required', 'array'],
            'line_ids.*' => ['integer'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.',
                'data' => null,
            ], 404);
        }

        $lineIds = collect($request->line_ids);

        // Verify all provided IDs belong to this cart
        $validCount = $cart->lines()->whereIn('id', $lineIds)->count();
        if ($lineIds->isNotEmpty() && $validCount !== $lineIds->count()) {
            return response()->json([
                'status' => 422,
                'message' => 'Oops! It looks like one of your items just sold out. Please review your cart before checking out.',
                'data' => null,
            ], 422);
        }

        // Deselect all lines, then select only the requested ones
        $cart->lines()->update(['selected' => false]);

        if ($lineIds->isNotEmpty()) {
            $cart->lines()->whereIn('id', $lineIds)->update(['selected' => true]);
        }

        $cart = $cart->recalculate();

        return CartResource::make($cart)
            ->additional([
                'status' => 200,
                'message' => 'Cart selection updated successfully.',
            ]);
    }
}
