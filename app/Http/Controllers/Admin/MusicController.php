<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Music;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MusicController extends Controller
{
    protected function storageDisk(): string
    {
        return app()->environment('production') ? 's3' : 'public';
    }

    public function index()
    {
        $music = Music::orderBy('created_at', 'desc')->get();

        return Inertia::render('admin/music/index', [
            'music' => $music,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/music/create');
    }

    public function store(Request $request)
    {

        $isS3Upload = $request->has('music_s3_key');

        if ($isS3Upload) {

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'thumbnail_s3_key' => ['nullable', 'string'],
                'music_s3_key' => ['required', 'string'],
                'is_active' => ['boolean'],
            ]);

            $path = $validated['music_s3_key'];
            $thumbnail = $validated['thumbnail_s3_key'] ?? null;
        } else {

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'thumbnail' => ['nullable', 'image', 'max:10240'],
                'music' => ['required', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:1073741824'],
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

    public function edit(Music $music)
    {
        $music->url = $music->url;
        $music->thumbnail_url = $music->thumbnail_url;

        return Inertia::render('admin/music/edit', [
            'music' => $music,
        ]);
    }

    public function update(Request $request, Music $music)
    {

        $isS3Upload = $request->has('music_s3_key') || $request->has('thumbnail_s3_key');

        if ($isS3Upload) {

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

                if ($music->thumbnail && Storage::disk($disk)->exists($music->thumbnail)) {
                    Storage::disk($disk)->delete($music->thumbnail);
                }
                $data['thumbnail'] = $validated['thumbnail_s3_key'];
            }

            if (! empty($validated['music_s3_key'])) {

                if ($music->path && Storage::disk($disk)->exists($music->path)) {
                    Storage::disk($disk)->delete($music->path);
                }
                $data['path'] = $validated['music_s3_key'];
            }
        } else {

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'thumbnail' => ['nullable', 'image', 'max:10240'],
                'music' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:1073741824'],
                'is_active' => ['boolean'],
            ]);

            $data = [
                'title' => $validated['title'],
                'is_active' => $request->input('is_active', true),
            ];

            $disk = $this->storageDisk();

            if ($request->hasFile('thumbnail')) {

                if ($music->thumbnail && Storage::disk($disk)->exists($music->thumbnail)) {
                    Storage::disk($disk)->delete($music->thumbnail);
                }
                $data['thumbnail'] = $request->file('thumbnail')->store('music/thumbnails', $disk);
            }

            if ($request->hasFile('music')) {

                if ($music->path && Storage::disk($disk)->exists($music->path)) {
                    Storage::disk($disk)->delete($music->path);
                }
                $data['path'] = $request->file('music')->store('music', $disk);
            }
        }

        $music->update($data);

        return to_route('admin.music.index')->with('success', 'Music updated successfully.');
    }

    public function destroy(Music $music)
    {

        $disksToCheck = ['public', 's3'];

        foreach ($disksToCheck as $disk) {
            try {
                if ($music->thumbnail && Storage::disk($disk)->exists($music->thumbnail)) {
                    Storage::disk($disk)->delete($music->thumbnail);
                }

                if ($music->path && Storage::disk($disk)->exists($music->path)) {
                    Storage::disk($disk)->delete($music->path);
                }
            } catch (\Exception $e) {

                continue;
            }
        }

        $music->delete();

        return to_route('admin.music.index')->with('success', 'Music deleted successfully.');
    }
}
