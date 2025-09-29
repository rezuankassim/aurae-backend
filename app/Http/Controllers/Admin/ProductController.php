<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
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

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        $validated = $request->validated();

        $description = $validated['content'] ?? '';
        $htmlDescription = $validated['html_content'] ?? '';

        $product->update([
            'product_type_id' => $validated['type'],
            'attribute_data' => [
                'name' => new TranslatedText(collect([
                    'en' => new Text($validated['name'])
                ])),
                'description' => new TranslatedText(collect([
                    'en' => new Text($htmlDescription)
                ])),
                'ori_description' => new TranslatedText(collect([
                    'en' => new Text($description)
                ])),
            ]
        ]);

        return to_route('admin.products.index')->with('success', 'Product updated successfully');
    }
}
