<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\TherapyUpdateRequest;
use App\Models\Music;
use App\Models\Therapy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CustomTherapyController extends Controller
{
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

    public function edit(Request $request, Therapy $customTherapy)
    {

        if ($customTherapy->user_id !== $request->user()->id || ! $customTherapy->is_custom) {
            return redirect()->route('custom-therapies.index')
                ->with('error', 'You do not have permission to edit this therapy.');
        }

        $customTherapy->image_url = $customTherapy->image_url;
        $customTherapy->music_url = $customTherapy->music_url;

        $music = Music::where('is_active', true)->orderBy('title')->get();

        return Inertia::render('custom-therapies/edit', [
            'customTherapy' => $customTherapy,
            'music' => $music,
        ]);
    }

    public function update(TherapyUpdateRequest $request, Therapy $customTherapy)
    {

        if ($customTherapy->user_id !== $request->user()->id || ! $customTherapy->is_custom) {
            return redirect()->route('custom-therapies.index')
                ->with('error', 'You do not have permission to edit this therapy.');
        }

        $validated = $request->validated();

        if ($request->hasFile('image')) {

            if ($customTherapy->image) {
                Storage::disk('public')->delete($customTherapy->image);
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
        $customTherapy->update($validated);

        return to_route('custom-therapies.index')->with('success', 'Custom therapy updated successfully.');
    }

    public function destroy(Request $request, Therapy $customTherapy)
    {

        if ($customTherapy->user_id !== $request->user()->id || ! $customTherapy->is_custom) {
            return redirect()->route('custom-therapies.index')
                ->with('error', 'You do not have permission to delete this therapy.');
        }

        if ($customTherapy->image) {
            Storage::disk('public')->delete($customTherapy->image);
        }

        $customTherapy->delete();

        return to_route('custom-therapies.index')->with('success', 'Custom therapy deleted successfully.');
    }
}
