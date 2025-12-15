<?php

namespace App\Http\Controllers;

use App\Models\News;
use Inertia\Inertia;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $news = News::query()
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();

        $news->transform(function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;

            return $item;
        });

        return Inertia::render('news/index', [
            'newsContent' => $news,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        abort_if(! $news->is_published, 404);

        transform($news, function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;
        });

        return Inertia::render('news/show', [
            'news' => $news,
        ]);
    }
}
