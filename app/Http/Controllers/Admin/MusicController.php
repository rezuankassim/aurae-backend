<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Music;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MusicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $music = Music::orderBy('created_at', 'desc')->get();

        return Inertia::render('admin/music/index', [
            'music' => $music,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/music/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'image', 'max:10240'], // 10MB max
            'music' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:1073741824'], // 1GB max
            'is_active' => ['boolean'],
        ]);

        $path = $request->file('music')->store('music', 'public');
        $thumbnail = null;

        if ($request->hasFile('thumbnail')) {
            $thumbnail = $request->file('thumbnail')->store('music/thumbnails', 'public');
        }

        Music::create([
            'title' => $validated['title'],
            'thumbnail' => $thumbnail,
            'path' => $path,
            'is_active' => $request->input('is_active', true),
            // Duration could be extracted if we had a library for it, skipping for now
        ]);

        return to_route('admin.music.index')->with('success', 'Music added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Music $music)
    {
        $music->url = $music->url;
        $music->thumbnail_url = $music->thumbnail_url;

        return Inertia::render('admin/music/edit', [
            'music' => $music,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Music $music)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'image', 'max:10240'],
            'music' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:1073741824'], // 1GB
            'is_active' => ['boolean'],
        ]);

        $data = [
            'title' => $validated['title'],
            'is_active' => $request->input('is_active', true),
        ];

        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($music->thumbnail && Storage::disk('public')->exists($music->thumbnail)) {
                Storage::disk('public')->delete($music->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('music/thumbnails', 'public');
        }

        if ($request->hasFile('music')) {
            // Delete old file
            if ($music->path && Storage::disk('public')->exists($music->path)) {
                Storage::disk('public')->delete($music->path);
            }
            $data['path'] = $request->file('music')->store('music', 'public');
        }

        $music->update($data);

        return to_route('admin.music.index')->with('success', 'Music updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Music $music)
    {
        if ($music->thumbnail && Storage::disk('public')->exists($music->thumbnail)) {
            Storage::disk('public')->delete($music->thumbnail);
        }

        if ($music->path && Storage::disk('public')->exists($music->path)) {
            Storage::disk('public')->delete($music->path);
        }

        $music->delete();

        return to_route('admin.music.index')->with('success', 'Music deleted successfully.');
    }
}
