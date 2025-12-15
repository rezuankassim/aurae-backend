<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductMediaCreateRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Lunar\Models\Product;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductMediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        return Inertia::render('admin/products/media/index', [
            'product' => $product,
            'images' => $product->getMedia('images')->map(function ($item) {
                $item->url = $item->getUrl();

                return $item;
            }),
            'withVariants' => $product->productOptions()->count() > 0,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductMediaCreateRequest $request, Product $product)
    {
        $validated = $request->validated();

        if (isset($validated['primary']) && $validated['primary']) {
            $mediaItems = $product->getMedia('images')->sortBy('order_column');
            $primaryMedia = $mediaItems->firstWhere('custom_properties.primary', true);
            if ($primaryMedia) {
                $primaryMedia->setCustomProperty('primary', false);
                $primaryMedia->save();
            }
        }

        $product->addMedia($validated['image'])
            ->withCustomProperties([
                'name' => $validated['name'] ?? null,
                'primary' => $request->has('primary') ? (bool) $validated['primary'] : false,
            ])
            ->toMediaCollection('images');

        return to_route('admin.products.media.index', $product->id)->with('success', 'Media added successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, Media $media)
    {
        if ($media->model_id !== $product->id) {
            return to_route('admin.products.media.index', $product->id)->with('error', 'Media not found for this product.');
        }

        $media->delete();

        Media::setNewOrder($product->getMedia('images')->sortBy('order_column')->map(function ($item) {
            return $item->id;
        })->toArray());

        return to_route('admin.products.media.index', $product->id)->with('success', 'Media deleted successfully.');
    }

    public function reorder(Product $product)
    {
        $images = $product->getMedia('images')->map(function ($item) {
            $item->url = $item->getUrl();

            return $item;
        });

        return Inertia::render('admin/products/media/reorder', [
            'product' => $product,
            'images' => $images,
            'withVariants' => $product->productOptions()->count() > 0,
        ]);
    }

    public function saveReorder(Request $request, Product $product)
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:media,id'],
        ]);

        Media::setNewOrder($request->order);

        return to_route('admin.products.media.index', $product->id)->with('success', 'Media order updated successfully.');
    }
}
