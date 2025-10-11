<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductPricingCreateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Models\Currency;
use Lunar\Models\Product;
use Lunar\Models\TaxClass;

class ProductPricingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        $taxClasses = TaxClass::all();

        return Inertia::render('admin/products/pricing/index', [
            'product' => $product->load(['prices', 'variants.taxClass']),
            'taxClasses' => $taxClasses,
            'withVariants' => $product->productOptions()->count() > 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductPricingCreateRequest $request, Product $product)
    {
        $validated = $request->validated();

        $onlyVariant = $product->variants()->first();
        $currency = $product->prices()->exists()
            ? $product->prices()->first()->currency
            : Currency::getDefault();

        $onlyVariant->update([
            'tax_class_id' => $validated['tax_class'],
            'tax_ref' => $validated['tax_ref'] ?? null,
        ]);

        $onlyVariant->prices()->updateOrCreate(
            [
                'currency_id' => $currency->id,
                'min_quantity' => 1,
            ],
            [
                'price' => (int) ($validated['price'] * (int) $currency->factor),
                'compare_price' => isset($validated['comparison_price']) ? (int) ($validated['comparison_price'] * (int) $currency->factor) : null,
            ]
        );

        return to_route('admin.products.pricing.index', $product)->with('success', 'Product pricing updated successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
