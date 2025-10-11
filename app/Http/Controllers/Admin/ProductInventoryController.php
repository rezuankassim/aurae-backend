<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductInventoryCreateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Models\Product;

class ProductInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        return Inertia::render('admin/products/inventory/index', [
            'product' => $product->load(['variants']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductInventoryCreateRequest $request, Product $product)
    {
        $validated = $request->validated();
        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['backorder'] = $validated['backorder'] ?? 0;

        $onlyVariant = $product->variants()->first();

        $onlyVariant->update([
            'stock' => $validated['stock'],
            'backorder' => $validated['backorder'],
            'purchasable' => $validated['purchasable'],
            'quantity_increment' => $validated['quantity_increment'],
            'min_quantity' => $validated['min_quantity'],
        ]);

        return to_route('admin.products.inventory.index', $product)->with('success', 'Product inventory updated successfully.');
    }
}
