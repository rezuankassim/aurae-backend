<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TherapyResource;
use App\Models\Therapy;
use Illuminate\Http\Request;

class TherapyController extends Controller
{
    /**
     * Display a listing of therapies.
     */
    public function index(Request $request)
    {
        $request->validate([
            'include_custom' => ['nullable', 'boolean'],
        ]);

        $query = Therapy::where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('created_at', 'desc');

        // If include_custom is true, include user's custom therapies
        if ($request->boolean('include_custom')) {
            $query->where(function ($q) use ($request) {
                $q->where('is_custom', false)
                    ->orWhere(function ($subQ) use ($request) {
                        $subQ->where('is_custom', true)
                            ->where('user_id', $request->user()->id);
                    });
            });
        } else {
            // Only show standard therapies (not custom)
            $query->where('is_custom', false);
        }

        $therapies = $query->get();

        return TherapyResource::collection($therapies)
            ->additional([
                'status' => 200,
                'message' => 'Therapies retrieved successfully.',
            ]);
    }
}
