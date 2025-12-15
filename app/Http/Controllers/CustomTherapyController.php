<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\TherapyUpdateRequest;
use App\Models\Therapy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CustomTherapyController extends Controller
{
    /**
     * Display a listing of user's custom therapies.
     */
    public function index(Request $request)
    {
        $customTherapies = Therapy::where('is_custom', true)
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('custom-therapies/index', [
            'customTherapies' => $customTherapies,
        ]);
    }

    /**
     * Show the form for editing the specified custom therapy.
     */
    public function edit(Request $request, Therapy $customTherapy)
    {
        // Ensure the therapy belongs to the authenticated user
        if ($customTherapy->user_id !== $request->user()->id || ! $customTherapy->is_custom) {
            return redirect()->route('custom-therapies.index')
                ->with('error', 'You do not have permission to edit this therapy.');
        }

        $customTherapy->image_url = $customTherapy->image_url;
        $customTherapy->music_url = $customTherapy->music_url;

        return Inertia::render('custom-therapies/edit', [
            'customTherapy' => $customTherapy,
        ]);
    }

    /**
     * Update the specified custom therapy in storage.
     */
    public function update(TherapyUpdateRequest $request, Therapy $customTherapy)
    {
        // Ensure the therapy belongs to the authenticated user
        if ($customTherapy->user_id !== $request->user()->id || ! $customTherapy->is_custom) {
            return redirect()->route('custom-therapies.index')
                ->with('error', 'You do not have permission to edit this therapy.');
        }

        $validated = $request->validated();

        // Handle file uploads
        if ($request->hasFile('image')) {
            // Remove old image if exists
            if ($customTherapy->image) {
                Storage::disk('public')->delete($customTherapy->image);
            }

            $validated['image'] = $request->file('image')->store('therapies/images', 'public');
        }
        if ($request->hasFile('music')) {
            // Remove old music if exists
            if ($customTherapy->music) {
                Storage::disk('public')->delete($customTherapy->music);
            }

            $validated['music'] = $request->file('music')->store('therapies/music', 'public');
        }

        $validated['configuration'] = collect([
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temp'),
            'light' => $request->input('light'),
        ])->toArray();
        $validated['is_active'] = $request->input('status', false);
        $customTherapy->update($validated);

        return to_route('custom-therapies.index')->with('success', 'Custom therapy updated successfully.');
    }

    /**
     * Remove the specified custom therapy from storage.
     */
    public function destroy(Request $request, Therapy $customTherapy)
    {
        // Ensure the therapy belongs to the authenticated user
        if ($customTherapy->user_id !== $request->user()->id || ! $customTherapy->is_custom) {
            return redirect()->route('custom-therapies.index')
                ->with('error', 'You do not have permission to delete this therapy.');
        }

        // Delete associated files
        if ($customTherapy->image) {
            Storage::disk('public')->delete($customTherapy->image);
        }
        if ($customTherapy->music) {
            Storage::disk('public')->delete($customTherapy->music);
        }

        $customTherapy->delete();

        return to_route('custom-therapies.index')->with('success', 'Custom therapy deleted successfully.');
    }
}
