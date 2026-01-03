<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsCreateRequest;
use App\Http\Requests\Admin\NewsUpdateRequest;
use App\Models\News;
use Carbon\Carbon;
use Inertia\Inertia;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $news = News::latest()->get();

        return Inertia::render('admin/news/index', [
            'newsContent' => $news,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/news/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NewsCreateRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('news', 'public');
            $validated['image'] = $path;
        }

        // Set is_published based on status field
        $validated['is_published'] = $validated['status'] === 'published';

        // Handle scheduled publishing date (optional)
        if (isset($validated['published_date']) && isset($validated['published_time'])) {
            $validated['published_at'] = Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'].' '.$validated['published_time']);
        } elseif ($validated['is_published']) {
            // If published now without scheduled date, set published_at to now
            $validated['published_at'] = now();
        } else {
            $validated['published_at'] = null;
        }

        // Remove temporary fields
        unset($validated['status'], $validated['published_date'], $validated['published_time']);

        News::create($validated);

        return to_route('admin.news.index')->with('success', 'News created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        transform($news, function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;
        });

        return Inertia::render('admin/news/show', [
            'news' => $news,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(News $news)
    {
        transform($news, function ($item) {
            $item->published_date = $item->published_at ? $item->published_at->format('d-m-Y') : null;
            $item->published_time = $item->published_at ? $item->published_at->format('H:i:s') : null;
            $item->status = $item->is_published ? 'published' : 'unpublished';

            return $item;
        });

        return Inertia::render('admin/news/edit', [
            'news' => $news,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NewsUpdateRequest $request, News $news)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('news', 'public');
            $validated['image'] = $path;
        }

        // Set is_published based on status field
        $validated['is_published'] = $validated['status'] === 'published';

        // Handle scheduled publishing date (optional)
        if (isset($validated['published_date']) && isset($validated['published_time'])) {
            $validated['published_at'] = Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'].' '.$validated['published_time']);
        } elseif ($validated['is_published'] && ! $news->published_at) {
            // If published now without scheduled date and no previous published_at, set to now
            $validated['published_at'] = now();
        } elseif (! $validated['is_published'] && ! isset($validated['published_date'])) {
            // If unpublished and no scheduled date provided, clear published_at
            $validated['published_at'] = null;
        }

        // Remove temporary fields
        unset($validated['status'], $validated['published_date'], $validated['published_time']);

        $news->update($validated);

        return to_route('admin.news.index')->with('success', 'News updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
