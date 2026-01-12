<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceBanner;
use Illuminate\Http\Request;

class MaintenanceBannerController extends Controller
{
    /**
     * Get all active maintenance banners for mobile app.
     */
    public function index(Request $request)
    {
        $banners = MaintenanceBanner::where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'image_url' => $banner->image ? asset('storage/'.$banner->image) : null,
                    'order' => $banner->order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }
}
