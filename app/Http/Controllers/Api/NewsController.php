<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;

class NewsController extends Controller
{
    /**
     * Display a listing of published news.
     */
    public function index()
    {
        $news = News::where('is_published', true)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->orderBy('published_at', 'desc')
            ->get();

        return NewsResource::collection($news)
            ->additional([
                'status' => 200,
                'message' => 'News retrieved successfully.',
            ]);
    }

    /**
     * Display the specified news.
     */
    public function show(News $news)
    {
        // Only show published news
        if (! $news->is_published || ($news->published_at && $news->published_at->isFuture())) {
            return response()->json([
                'status' => 404,
                'message' => 'News not found.',
            ], 404);
        }

        return NewsResource::make($news)
            ->additional([
                'status' => 200,
                'message' => 'News retrieved successfully.',
            ]);
    }
}
