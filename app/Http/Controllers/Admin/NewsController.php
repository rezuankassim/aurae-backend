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
    public function index()
    {
        $news = News::latest()->get();

        return Inertia::render('admin/news/index', [
            'newsContent' => $news,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/news/create');
    }

    public function store(NewsCreateRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('news', 'public');
            $validated['image'] = $path;
        }

        $validated['is_published'] = $validated['status'] === 'published';

        if (isset($validated['published_date']) && isset($validated['published_time'])) {
            $validated['published_at'] = Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'].' '.$validated['published_time']);
        } elseif ($validated['is_published']) {

            $validated['published_at'] = now();
        } else {
            $validated['published_at'] = null;
        }

        unset($validated['status'], $validated['published_date'], $validated['published_time']);

        News::create($validated);

        return to_route('admin.news.index')->with('success', 'News created successfully');
    }

    public function show(News $news)
    {
        transform($news, function ($item) {
            $item->image_url = $item->image ? asset('storage/'.$item->image) : null;
        });

        return Inertia::render('admin/news/show', [
            'news' => $news,
        ]);
    }

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

    public function update(NewsUpdateRequest $request, News $news)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('news', 'public');
            $validated['image'] = $path;
        }

        $validated['is_published'] = $validated['status'] === 'published';

        if (isset($validated['published_date']) && isset($validated['published_time'])) {
            $validated['published_at'] = Carbon::createFromFormat('d-m-Y H:i:s', $validated['published_date'].' '.$validated['published_time']);
        } elseif ($validated['is_published'] && ! $news->published_at) {

            $validated['published_at'] = now();
        } elseif (! $validated['is_published'] && ! isset($validated['published_date'])) {

            $validated['published_at'] = null;
        }

        unset($validated['status'], $validated['published_date'], $validated['published_time']);

        $news->update($validated);

        return to_route('admin.news.index')->with('success', 'News updated successfully');
    }

    public function unpublish(News $news)
    {
        $news->update([
            'is_published' => false,
            'published_at' => null,
        ]);

        return back()->with('success', 'News unpublished successfully.');
    }

    public function destroy(string $id) {}
}
