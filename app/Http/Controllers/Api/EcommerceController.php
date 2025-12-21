<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use Illuminate\Http\Request;
use Lunar\Models\Collection;

class EcommerceController extends Controller
{
    public function collections(Request $request)
    {
        $collections = Collection::query()
            ->with([
                'thumbnail', 
                'products.media',
                'products.variants.basePrices.currency', 
                'products.variants.basePrices.priceable',
                'products.variants.values.option',
                'products.defaultUrl'
            ])
            ->get();

        return CollectionResource::collection($collections)
            ->additional([
                'status' => 200,
                'message' => 'Collections retrieved successfully.',
            ]);
    }
}
