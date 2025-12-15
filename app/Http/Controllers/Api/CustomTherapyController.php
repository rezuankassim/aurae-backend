<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomTherapyResource;
use App\Models\Therapy;
use Illuminate\Http\Request;

class CustomTherapyController extends Controller
{
    /**
     * Display a listing of the user's custom therapies.
     */
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

    /**
     * Store a newly created custom therapy in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:10240'], // 10MB max
            'music' => ['required', 'file', 'mimes:mp3,wav,ogg', 'max:20480'], // 20MB max
            'duration' => ['required', 'numeric', 'min:0'],
            'temperature' => ['required', 'numeric'],
            'light' => ['required', 'numeric'],
            'status' => ['nullable', 'boolean'],
        ]);

        $validated = $request->only(['name', 'description']);

        // Handle file uploads
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('therapies/images', 'public');
        }

        $validated['music'] = $request->file('music')->store('therapies/music', 'public');
        $validated['configuration'] = [
            'duration' => $request->input('duration'),
            'temperature' => $request->input('temperature'),
            'light' => $request->input('light'),
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
}
