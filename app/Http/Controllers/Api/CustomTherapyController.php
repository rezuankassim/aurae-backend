<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomTherapyResource;
use App\Models\Therapy;
use Illuminate\Http\Request;

class CustomTherapyController extends Controller
{
    public function index(Request $request)
    {
        $customTherapies = Therapy::where('is_custom', true)
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return CustomTherapyResource::collection($customTherapies)
            ->additional([
                'status' => 200,
                'message' => 'Custom therapies retrieved successfully.',
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:10240'],
            'music_id' => ['required', 'exists:music,id'],
            'duration' => ['required', 'numeric', 'min:0'],
            'temperature' => ['required', 'numeric'],
            'light' => ['required', 'numeric'],
            'color_led' => ['required', 'string', 'in:Off,Red,Orange,Yellow,Green,Blue,Purple,White,Cyan'],
            'status' => ['nullable', 'boolean'],
        ]);

        $validated = $request->only(['name', 'description']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('therapies/images', 'public');
        }

        $validated['music_id'] = $request->input('music_id');
        $validated['configuration'] = [
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temperature'),
            'light' => $request->input('light'),
            'color_led' => $request->input('color_led'),
        ];
        $validated['is_active'] = $request->input('status', true);
        $validated['is_custom'] = true;
        $validated['user_id'] = $request->user()->id;

        $therapy = Therapy::create($validated);

        return CustomTherapyResource::make($therapy)
            ->additional([
                'status' => 201,
                'message' => 'Custom therapy created successfully.',
            ]);
    }

    public function destroy(Request $request, Therapy $customTherapy)
    {

        if (! $customTherapy->is_custom || $customTherapy->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 403,
                'message' => 'You are not authorized to delete this therapy.',
            ], 403);
        }

        if ($customTherapy->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($customTherapy->image);
        }

        $customTherapy->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Custom therapy deleted successfully.',
        ]);
    }
}
