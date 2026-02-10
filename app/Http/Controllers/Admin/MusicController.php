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
     * Get the storage disk based on environment.
     */
    protected function storageDisk(): string
    {
        return app()->environment('production') ? 's3' : 'public';
    }
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
        // Determine if this is a direct S3 upload or traditional file upload
        $isS3Upload = $request->has('music_s3_key');

        if ($isS3Upload) {
            // Direct S3 upload - validate S3 keys
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'thumbnail_s3_key' => ['nullable', 'string'],
                'music_s3_key' => ['required', 'string'],
                'is_active' => ['boolean'],
            ]);

            $path = $validated['music_s3_key'];
            $thumbnail = $validated['thumbnail_s3_key'] ?? null;
        } else {
            // Traditional file upload (development)
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'thumbnail' => ['nullable', 'image', 'max:10240'], // 10MB max
                'music' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:1073741824'], // 1GB max
                'is_active' => ['boolean'],
            ]);

            $disk = $this->storageDisk();
            $path = $request->file('music')->store('music', $disk);
            $thumbnail = null;

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail')->store('music/thumbnails', $disk);
            }
        }

        Music::create([
            'title' => $validated['title'],
            'thumbnail' => $thumbnail,
            'path' => $path,
            'is_active' => $request->input('is_active', true),
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
        // Determine if this is a direct S3 upload or traditional file upload
        $isS3Upload = $request->has('music_s3_key') || $request->has('thumbnail_s3_key');

        if ($isS3Upload) {
            // Direct S3 upload
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'thumbnail_s3_key' => ['nullable', 'string'],
                'music_s3_key' => ['nullable', 'string'],
                'is_active' => ['boolean'],
            ]);

            $data = [
                'title' => $validated['title'],
                'is_active' => $request->input('is_active', true),
            ];

            $disk = $this->storageDisk();

            if (! empty($validated['thumbnail_s3_key'])) {
                // Delete old thumbnail
                if ($music->thumbnail && Storage::disk($disk)->exists($music->thumbnail)) {
                    Storage::disk($disk)->delete($music->thumbnail);
                }
                $data['thumbnail'] = $validated['thumbnail_s3_key'];
            }

            if (! empty($validated['music_s3_key'])) {
                // Delete old file
                if ($music->path && Storage::disk($disk)->exists($music->path)) {
                    Storage::disk($disk)->delete($music->path);
                }
                $data['path'] = $validated['music_s3_key'];
            }
        } else {
            // Traditional file upload (development)
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

            $disk = $this->storageDisk();

            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail
                if ($music->thumbnail && Storage::disk($disk)->exists($music->thumbnail)) {
                    Storage::disk($disk)->delete($music->thumbnail);
                }
                $data['thumbnail'] = $request->file('thumbnail')->store('music/thumbnails', $disk);
            }

            if ($request->hasFile('music')) {
                // Delete old file
                if ($music->path && Storage::disk($disk)->exists($music->path)) {
                    Storage::disk($disk)->delete($music->path);
                }
                $data['path'] = $request->file('music')->store('music', $disk);
            }
        }

        $music->update($data);

        return to_route('admin.music.index')->with('success', 'Music updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Music $music)
    {
        $disk = $this->storageDisk();

        if ($music->thumbnail && Storage::disk($disk)->exists($music->thumbnail)) {
            Storage::disk($disk)->delete($music->thumbnail);
        }

        if ($music->path && Storage::disk($disk)->exists($music->path)) {
            Storage::disk($disk)->delete($music->path);
        }

        $music->delete();

        return to_route('admin.music.index')->with('success', 'Music deleted successfully.');
    }
}
