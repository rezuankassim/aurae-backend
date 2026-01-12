<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaintenanceBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $banners = MaintenanceBanner::orderBy('order')->get();

        return Inertia::render('admin/maintenance-banners/index', [
            'banners' => $banners,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/maintenance-banners/create');
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
            $path = $request->file('image')->store('maintenance-banners', 'public');
            $validated['image'] = $path;
        }

        MaintenanceBanner::create($validated);

        return to_route('admin.maintenance-banners.index')
            ->with('success', 'Maintenance banner created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MaintenanceBanner $maintenanceBanner)
    {
        transform($maintenanceBanner, function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;
        });

        return Inertia::render('admin/maintenance-banners/show', [
            'banner' => $maintenanceBanner,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaintenanceBanner $maintenanceBanner)
    {
        return Inertia::render('admin/maintenance-banners/edit', [
            'banner' => $maintenanceBanner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaintenanceBanner $maintenanceBanner)
    {
        $validated = $request->validate([
            'image' => ['nullable', 'image', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($maintenanceBanner->image) {
                Storage::disk('public')->delete($maintenanceBanner->image);
            }

            $path = $request->file('image')->store('maintenance-banners', 'public');
            $validated['image'] = $path;
        }

        $maintenanceBanner->update($validated);

        return to_route('admin.maintenance-banners.index')
            ->with('success', 'Maintenance banner updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaintenanceBanner $maintenanceBanner)
    {
        // Delete image file
        if ($maintenanceBanner->image) {
            Storage::disk('public')->delete($maintenanceBanner->image);
        }

        $maintenanceBanner->delete();

        return to_route('admin.maintenance-banners.index')
            ->with('success', 'Maintenance banner deleted successfully.');
    }
}
