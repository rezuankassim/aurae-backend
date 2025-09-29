<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
}
