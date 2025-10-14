<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TherapyCreateRequest;
use App\Http\Requests\Admin\TherapyUpdateRequest;
use App\Models\Therapy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TherapyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $therapies = Therapy::all();

        return Inertia::render('admin/therapies/index', [
            'therapies' => $therapies,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/therapies/create');
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

        $validated['music'] = $request->file('music')->store('therapies/music', 'public');
        $validated['configuration'] = collect([
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temp'),
            'light' => $request->input('light'),
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

        return Inertia::render('admin/therapies/edit', [
            'therapy' => $therapy,
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
        if ($request->hasFile('music')) {
            // Remove old music if exists
            if ($therapy->music) {
                Storage::disk('public')->delete($therapy->music);
            }

            $validated['music'] = $request->file('music')->store('therapies/music', 'public');
        }

        $validated['configuration'] = collect([
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temp'),
            'light' => $request->input('light'),
        ])->toArray();
        $validated['is_active'] = $request->input('status', false);
        $therapy->update($validated);

        return to_route('admin.therapies.index')->with('success', 'Therapy updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
