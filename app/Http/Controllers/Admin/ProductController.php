<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductUpdateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\Currency;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Tag;
use Lunar\Models\TaxClass;

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
        $productTypes = ProductType::all();

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'draftCount' => $draftCount,
            'productType' => $productTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'exists:lunar_product_types,id'],
            'sku' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
        ]);

        $currency = Currency::getDefault();

        $nameAttribute = Attribute::whereAttributeType(
            Product::morphName()
        )
            ->whereHandle('name')
            ->first()
            ->type;

        $product = Product::create([
            'status' => 'draft',
            'product_type_id' => $request->type,
            'attribute_data' => [
                'name' => new $nameAttribute(collect([
                    'en' => new Text($request->name),
                ])),
            ],
        ]);
        $variant = $product->variants()->create([
            'tax_class_id' => TaxClass::getDefault()->id,
            'sku' => $request->sku,
        ]);
        $variant->prices()->create([
            'min_quantity' => 1,
            'currency_id' => $currency->id,
            'price' => (int) bcmul($request->base_price, $currency->factor),
        ]);

        return to_route('admin.products.index')->with('success', 'Product created successfully.');
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
            'withVariants' => $product->productOptions()->count() > 0,
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
                    'en' => new Text($validated['name']),
                ])),
                'description' => new TranslatedText(collect([
                    'en' => new Text($htmlDescription),
                ])),
                'ori_description' => new TranslatedText(collect([
                    'en' => new Text($description),
                ])),
            ],
        ]);

        // Update tags
        if (isset($validated['tags'])) {
            $product->tags()->sync($validated['tags']);
        }

        return back()->with('success', 'Product updated successfully');
    }

    /**
     * Update the product status.
     */
    public function updateStatus(Request $request, Product $product)
    {
        $request->validate([
            'status' => ['required', 'in:draft,published'],
        ]);

        $product->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Product status updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete all related data
        foreach ($product->variants as $variant) {
            $variant->basePrices()->delete();
            $variant->values()->detach();
            $variant->delete();
        }

        // Delete product options
        $product->productOptions()->detach();

        // Delete collections
        $product->collections()->detach();

        // Delete tags
        $product->tags()->detach();

        // Delete media
        $product->clearMediaCollection();

        // Delete the product
        $product->delete();

        return to_route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
