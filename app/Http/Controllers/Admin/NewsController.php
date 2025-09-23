<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsCreateRequest;
use App\Http\Requests\Admin\NewsUpdateRequest;
use App\Models\News;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        if (isset($validated['published_date']) && isset($validated['published_time'])) {
            if (now()->greaterThanOrEqualTo(\DateTime::createFromFormat('d-m-Y H:i:s', $validated['published_date'] . ' ' . $validated['published_time']))) {
                $validated['is_published'] = true;
            } else {
                $validated['is_published'] = false;
            }
            $validated['published_at'] = \DateTime::createFromFormat('d-m-Y H:i:s', $validated['published_date'] . ' ' . $validated['published_time']);
        }

        News::create($validated);

        return to_route('admin.news.index')->with('success', 'News created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        transform($news, function ($item) {
            $item->image_url = $item->image ? asset('storage/' . $item->image) : null;
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

        if (isset($validated['published_date']) && isset($validated['published_time'])) {
            if (now()->greaterThanOrEqualTo(Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'] . ' ' . $validated['published_time']))) {
                $validated['is_published'] = true;
            } else {
                $validated['is_published'] = false;
            }
            $validated['published_at'] = Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'] . ' ' . $validated['published_time']);
        } else {
            $validated['is_published'] = false;
            $validated['published_at'] = null;
        }

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
