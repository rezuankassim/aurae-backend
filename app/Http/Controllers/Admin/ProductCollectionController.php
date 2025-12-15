<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductCollectionCreateRequest;
use Inertia\Inertia;
use Lunar\Models\Collection;
use Lunar\Models\Product;

class ProductCollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        return Inertia::render('admin/products/collections/index', [
            'product' => $product->load('collections'),
            'collections' => Collection::where('parent_id', null)->get(),
            'withVariants' => $product->productOptions()->count() > 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductCollectionCreateRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product->collections()->attach($validated['collection_id']);

        return to_route('admin.products.collections.index', $product->id)->with('success', 'Collection attached successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, Collection $collection)
    {
        $product->collections()->detach($collection->id);

        return to_route('admin.products.collections.index', $product->id)->with('success', 'Collection removed successfully.');
    }
}
