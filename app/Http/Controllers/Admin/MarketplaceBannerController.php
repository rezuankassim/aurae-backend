<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MarketplaceBannerController extends Controller
{
    public function index()
    {
        $banners = MarketplaceBanner::orderBy('order')->get();

        return Inertia::render('admin/marketplace-banners/index', [
            'banners' => $banners,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/marketplace-banners/create');
    }

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

    public function show(MarketplaceBanner $marketplaceBanner)
    {
        transform($marketplaceBanner, function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;
        });

        return Inertia::render('admin/marketplace-banners/show', [
            'banner' => $marketplaceBanner,
        ]);
    }

    public function edit(MarketplaceBanner $marketplaceBanner)
    {
        return Inertia::render('admin/marketplace-banners/edit', [
            'banner' => $marketplaceBanner,
        ]);
    }

    public function update(Request $request, MarketplaceBanner $marketplaceBanner)
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {

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

    public function destroy(MarketplaceBanner $marketplaceBanner)
    {

        if ($marketplaceBanner->image) {
            Storage::disk('public')->delete($marketplaceBanner->image);
        }

        $marketplaceBanner->delete();

        return to_route('admin.marketplace-banners.index')
            ->with('success', 'Marketplace banner deleted successfully.');
    }
}
