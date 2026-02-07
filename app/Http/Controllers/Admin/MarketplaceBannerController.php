<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MarketplaceBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = MarketplaceBanner::orderBy('order')->get();

        return Inertia::render('admin/marketplace-banners/index', [
            'banners' => $banners,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/marketplace-banners/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('marketplace-banners', 'public');
            $validated['image'] = $path;
        }

        MarketplaceBanner::create($validated);

        return to_route('admin.marketplace-banners.index')
            ->with('success', 'Marketplace banner created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MarketplaceBanner $marketplaceBanner)
    {
        transform($marketplaceBanner, function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;
        });

        return Inertia::render('admin/marketplace-banners/show', [
            'banner' => $marketplaceBanner,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MarketplaceBanner $marketplaceBanner)
    {
        return Inertia::render('admin/marketplace-banners/edit', [
            'banner' => $marketplaceBanner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarketplaceBanner $marketplaceBanner)
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($marketplaceBanner->image) {
                Storage::disk('public')->delete($marketplaceBanner->image);
            }

            $path = $request->file('image')->store('marketplace-banners', 'public');
            $validated['image'] = $path;
        }

        $marketplaceBanner->update($validated);

        return to_route('admin.marketplace-banners.index')
            ->with('success', 'Marketplace banner updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MarketplaceBanner $marketplaceBanner)
    {
        // Delete image file
        if ($marketplaceBanner->image) {
            Storage::disk('public')->delete($marketplaceBanner->image);
        }

        $marketplaceBanner->delete();

        return to_route('admin.marketplace-banners.index')
            ->with('success', 'Marketplace banner deleted successfully.');
    }
}
