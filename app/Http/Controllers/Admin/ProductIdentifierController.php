<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductIdentifierCreateRequest;
use Inertia\Inertia;
use Lunar\Models\Product;

class ProductIdentifierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        return Inertia::render('admin/products/identifiers/index', [
            'product' => $product->load(['variants']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductIdentifierCreateRequest $request, Product $product)
    {
        $validated = $request->validated();

        $onlyVariant = $product->variants()->first();

        $onlyVariant->update([
            'sku' => $validated['sku'],
            'mpn' => $validated['mpn'] ?? null,
            'ean' => $validated['ean'] ?? null,
            'gtin' => $validated['gtin'] ?? null,
        ]);

        return to_route('admin.products.identifiers.index', $product)->with('success', 'Product identifiers updated successfully.');
    }
}
