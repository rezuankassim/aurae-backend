<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Lunar\Models\Product;
use Illuminate\Support\Str;
use Lunar\Models\Language;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;
use Lunar\Models\Contracts\ProductOption as ProductOptionContract;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;

// $variant = $product->variants()->create([
//     'tax_class_id' => TaxClass::getDefault()->id,
//     'sku' => $data['sku'],
// ]);
// $variant->prices()->create([
//     'min_quantity' => 1,
//     'currency_id' => $currency->id,
//     'price' => (int) bcmul($data['base_price'], $currency->factor),
// ]);
class ProductVariantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        $options = $product->productOptions()->with('values')->get();

        $variants = $product->variants()->with('values.option', 'basePrices')
            ->whereHas('values')
            ->whereHas('basePrices')
            ->get();

        $with_variants = $product->productOptions()->count() > 0;

        return Inertia::render('admin/products/variants/index', [
            'product' => $product,
            'options' => $options,
            'variants' => $variants,
            'withVariants' => $with_variants,
        ]);
    }

    /**
     * Configure options for the product.
     */
    public function configure(Product $product)
    {
        return Inertia::render('admin/products/variants/configure', [
            'product' => $product,
            'options' => $product->productOptions()->with('values')->get(),
            'withVariants' => $product->productOptions()->count() > 0,
        ]);
    }


    /**
     * Update all variants of the product.
     */
    public function updateAll(Request $request, Product $product)
    {
        $data = $request->all();

        $variants = $product->variants->load('basePrices');

        foreach ($variants as $variant) {
            $variantData = [];
            if (isset($data[$variant->id . '-sku'])) {
                $variantData['sku'] = $data[$variant->id . '-sku'];
            }

            if (isset($data[$variant->id . '-stock'])) {
                $variantData['stock'] = (int)$data[$variant->id . '-stock'];
            }

            if (isset($data[$variant->id . '-price'])) {
                $price = (int) ($data[$variant->id . '-price'] * 100);

                $variant->basePrices->first()->update(['price' => $price]);
            }

            if (!empty($variantData)) {
                $variant->update($variantData);
            }
        }

        return to_route('admin.products.variants.index', $product->id)->with('success', 'Variants updated successfully.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $data = $request->all();

        $language = Language::getDefault();
        $taxClass = TaxClass::getDefault();

        $grouped = collect($data)->filter(fn ($item, $key) => !Str::endsWith($key, '-name'))->groupBy(function ($item, $key) {
            return explode('-', $key)[0];
        })->toArray();

        $optionsId = [];
        $optionsOrder = 1;
        if (count($grouped) == 0) {
            // No options, remove all exisiting options variants except the first one
            $variant = $product->variants()->first();
            $variant->values()->detach();

            $product->productOptions()
                ->each(
                    fn (ProductOptionContract $productOption) => $productOption->delete()
                );

            $product->productOptions()->shared()->detach();
            $product->variants()
                ->where('id', '!=', $variant->id)
                ->get()
                ->each(
                    fn (ProductVariantContract $variant) => $variant->delete()
                );
        }


        foreach ($grouped as $key => $values) {
            if (!ProductOption::where('id', $key)->exists()) {
                $productOption = new ProductOption([
                    'name' => [$language->code => $data[$key . '-name']],
                    'handle' => Str::slug($data[$key . '-name']),
                    'label' => [$language->code => $data[$key . '-name']],
                ]);
            } else {
                $productOption = ProductOption::find($key);
                $productOption->name = [$language->code => $data[$key . '-name']];
                $productOption->handle = Str::slug($data[$key . '-name']);
                $productOption->label = [$language->code => $data[$key . '-name']];
            }

            $productOption->save();
            $optionsId[$productOption->id] = ['position' => $optionsOrder++];

            $optionValues = collect($data)->filter(fn ($item, $k) => Str::startsWith($k, $key . '-value-'))->map(function ($item, $k) use ($language) {
                return [
                    'id' => explode('-', $k)[2] ?? null,
                    'name' => [$language->code => $item],
                    'handle' => Str::slug($item),
                ];
            })->values()->toArray();

            foreach ($optionValues as $index => $optionValue) {
                if (isset($optionValue['id']) && ProductOptionValue::where('id', $optionValue['id'])->exists()) {
                    $productOptionValue = ProductOptionValue::find($optionValue['id']);
                    $productOptionValue->name = $optionValue['name'];
                    $productOptionValue->position = $index + 1;
                } else {
                    $productOptionValue = new ProductOptionValue([
                        'product_option_id' => $productOption->id,
                        'name' => $optionValue['name'],
                        'position' => $index + 1,
                    ]);
                }
                $productOptionValue->save();
            }
        }

        $product->productOptions()->sync($optionsId);

        $product_options_values = $product->productOptions->load('values')->mapWithKeys(function ($option) {
            return [$option->id => $option->values->pluck('id')->toArray()];
        })->toArray();
        $combinations = $this->cartesian($product_options_values);

        $variants = $product->variants->load(['basePrices.currency', 'basePrices.priceable', 'values.option'])->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->basePrices->first()?->price->decimal ?: 0,
                'stock' => $variant->stock,
                'values' => $variant->values->mapWithKeys(
                    fn ($value) => [$value->option->id => $value->id]
                )->toArray(),
            ];
        })->toArray();
;
        foreach ($combinations as $combination) {
            // See if this combination already has a variant
            $exists = collect($variants)->first(function ($variant) use ($combination) {
                $valueDifference = array_diff_assoc($combination, $variant['values']);
                return count($valueDifference) == 0;
            });
            if ($exists) {
                continue;
            }

            $firstVariant = $product->variants()->first();
            $variant = new ProductVariant([
                'tax_class_id' => $taxClass->id,
                'product_id' => $product->id,
                'sku' => $firstVariant['sku'],
                'stock' => $firstVariant['stock'],
            ]);
            $variant->save();

            $variant->values()->sync($combination);

            $basePrice = $firstVariant->basePrices->first()->replicate();
            $basePrice->priceable_id = $variant->id;
            $basePrice->save();
        }

        return to_route('admin.products.variants.index', $request->product->id)->with('success', 'Variant saved successfully.');
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

    protected function cartesian(array $input)
    {
        foreach ($input as $dimension) {
            if (empty($dimension)) return [];
        }
    
        $result = collect($input)->reduce(
            function (?Collection $carry, $items) {
                $items = collect($items);
    
                // First dimension: seed with single-item arrays
                if ($carry === null) {
                    return $items->map(fn($i) => [$i]);
                }
    
                // Combine previous combos with current items
                return $carry->flatMap(
                    fn($combo) => $items->map(fn($i) => array_merge($combo, [$i]))
                );
            },
            null // IMPORTANT: start with null, not collect()
        );
    
        return $result ? $result->values()->toArray() : [];
    }
}
