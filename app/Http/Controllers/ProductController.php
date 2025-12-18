<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index()
    {
        $collectionGroups = CollectionGroup::with([
            'collections' => function ($query) {
                $query->whereNull('parent_id')
                    ->with([
                        'products' => function ($q) {
                            $q->where('status', 'published')
                                ->with([
                                    'variants.prices.currency',
                                    'productType',
                                    'thumbnail',
                                ]);
                        },
                    ]);
            },
        ])->get();

        // Also get all published products not filtered by collection
        $products = Product::where('status', 'published')
            ->with([
                'variants.prices.currency',
                'productType',
                'thumbnail',
                'collections',
            ])
            ->get();

        return Inertia::render('products/index', [
            'collectionGroups' => $collectionGroups,
            'products' => $products,
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load([
            'variants' => function ($query) {
                $query->with([
                    'prices.currency',
                    'values.option',
                ]);
            },
            'productType',
            'media',
            'collections.group',
        ]);

        // Get related products from the same collections
        $relatedProducts = Product::where('status', 'published')
            ->where('id', '!=', $product->id)
            ->whereHas('collections', function ($query) use ($product) {
                $query->whereIn('lunar_collections.id', $product->collections->pluck('id'));
            })
            ->with([
                'variants.prices.currency',
                'productType',
                'thumbnail',
            ])
            ->limit(4)
            ->get();

        return Inertia::render('products/show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
