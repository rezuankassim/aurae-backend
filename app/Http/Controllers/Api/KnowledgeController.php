<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KnowledgeResource;
use App\Models\Knowledge;

class KnowledgeController extends Controller
{
    /**
     * Display a listing of published knowledge.
     */
    public function index()
    {
        $knowledge = Knowledge::where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderBy('published_at', 'desc')
            ->get();

        return KnowledgeResource::collection($knowledge)
            ->additional([
                'status' => 200,
                'message' => 'Knowledge retrieved successfully.',
            ]);
    }

    /**
     * Display the specified knowledge.
     */
    public function show(Knowledge $knowledge)
    {
        // Only show published knowledge
        if (! $knowledge->is_published || ($knowledge->published_at && $knowledge->published_at->isFuture())) {
            return response()->json([
                'status' => 404,
                'message' => 'Knowledge not found.',
            ], 404);
        }

        return KnowledgeResource::make($knowledge)
            ->additional([
                'status' => 200,
                'message' => 'Knowledge retrieved successfully.',
            ]);
    }
}
