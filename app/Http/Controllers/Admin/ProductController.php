<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Tag;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::query()
            ->with(['productType', 'brand', 'variants'])
            ->get();

        $draftCount = Product::where('status', 'draft')->count();

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'draftCount' => $draftCount,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['productType', 'brand', 'variants', 'tags']);

        $productTypes = ProductType::all();
        $tags = Tag::all();

        $product->tags_array = $product->tags->pluck('id')->toArray();

        return Inertia::render('admin/products/edit', [
            'product' => $product,
            'productTypes' => $productTypes,
            'tags' => $tags,
        ]);
    }
}
