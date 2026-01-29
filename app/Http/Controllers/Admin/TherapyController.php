<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TherapyCreateRequest;
use App\Http\Requests\Admin\TherapyUpdateRequest;
use App\Models\Music;
use App\Models\Therapy;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TherapyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $therapies = Therapy::orderBy('order', 'asc')->where('is_custom', 0)->get();

        $therapies->map(function ($therapy) {
            $therapy->image_url = $therapy->image_url;
            $therapy->music_url = $therapy->music_url;

            return $therapy;
        });

        return Inertia::render('admin/therapies/index', [
            'therapies' => $therapies,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $music = Music::where('is_active', true)->orderBy('title')->get();

        return Inertia::render('admin/therapies/create', [
            'music' => $music,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TherapyCreateRequest $request)
    {
        $validated = $request->validated();

        // Handle file uploads
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('therapies/images', 'public');
        }

        if ($request->filled('music_id')) {
            $validated['music_id'] = $request->input('music_id');
        }

        $validated['configuration'] = collect([
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temp'),
            'light' => $request->input('light'),
            'color_led' => $request->input('color_led'),
        ])->toArray();
        $validated['is_active'] = $request->input('status', false);
        $therapy = Therapy::create($validated);

        return to_route('admin.therapies.index')->with('success', 'Therapy created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Therapy $therapy)
    {
        $therapy->image_url = $therapy->image_url;
        $therapy->music_url = $therapy->music_url;

        $music = Music::where('is_active', true)->orderBy('title')->get();

        return Inertia::render('admin/therapies/edit', [
            'therapy' => $therapy,
            'music' => $music,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TherapyUpdateRequest $request, Therapy $therapy)
    {
        $validated = $request->validated();

        // Handle file uploads
        if ($request->hasFile('image')) {
            // Remove old image if exists
            if ($therapy->image) {
                Storage::disk('public')->delete($therapy->image);
            }

            $validated['image'] = $request->file('image')->store('therapies/images', 'public');
        }

        if ($request->filled('music_id')) {
            $validated['music_id'] = $request->input('music_id');
        }

        $validated['configuration'] = collect([
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temp'),
            'light' => $request->input('light'),
            'color_led' => $request->input('color_led'),
        ])->toArray();
        $validated['is_active'] = $request->input('status', false);
        $therapy->update($validated);

        return to_route('admin.therapies.index')->with('success', 'Therapy updated successfully.');
    }

    /**
     * Update the order of therapies.
     */
    public function reorder()
    {
        $therapies = request()->validate([
            'therapies' => 'required|array',
            'therapies.*.id' => 'required|exists:therapies,id',
            'therapies.*.order' => 'required|integer',
        ]);

        foreach ($therapies['therapies'] as $therapy) {
            Therapy::where('id', $therapy['id'])->update(['order' => $therapy['order']]);
        }

        return back()->with('success', 'Therapies reordered successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Therapy $therapy)
    {
        // Delete associated image file if exists
        if ($therapy->image) {
            Storage::disk('public')->delete($therapy->image);
        }

        $therapy->delete();

        return to_route('admin.therapies.index')->with('success', 'Therapy deleted successfully.');
    }
}
