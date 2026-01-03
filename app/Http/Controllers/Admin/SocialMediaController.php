<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialMediaUpdateRequest;
use App\Models\SocialMedia;
use Inertia\Inertia;

class SocialMediaController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $socialMedia = SocialMedia::firstOrCreate();

        return Inertia::render('admin/social-media/edit', [
            'socialMedia' => $socialMedia,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SocialMediaUpdateRequest $request)
    {
        $validated = $request->validated();

        $socialMedia = SocialMedia::first();
        $socialMedia->links = [
            'facebook' => $validated['facebook'] ?? null,
            'xhs' => $validated['xhs'] ?? null,
            'instagram' => $validated['instagram'] ?? null,
        ];
        $socialMedia->save();

        return to_route('admin.social-media.edit')->with('success', 'Social media links updated successfully.');
    }
}
