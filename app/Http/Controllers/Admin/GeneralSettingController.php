<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GeneralSettingUpdateRequest;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GeneralSettingController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $generalSetting = GeneralSetting::firstOrCreate();

        return Inertia::render('admin/general-settings/edit', [
            'generalSetting' => $generalSetting,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GeneralSettingUpdateRequest $request)
    {
        $validated = $request->validated();

        $generalSetting = GeneralSetting::first();
        $generalSetting->contact_no = $validated['contact_no'];

        // Handle APK file upload
        if ($request->hasFile('apk_file')) {
            // Delete old APK file if exists
            if ($generalSetting->apk_file_path && Storage::disk('public')->exists($generalSetting->apk_file_path)) {
                Storage::disk('public')->delete($generalSetting->apk_file_path);
            }

            // Store new APK file
            $file = $request->file('apk_file');
            $path = $file->store('apk', 'public');

            $generalSetting->apk_file_path = $path;
            $generalSetting->apk_file_size = $file->getSize();
        }

        // Update APK version and release notes
        if ($request->filled('apk_version')) {
            $generalSetting->apk_version = $validated['apk_version'];
        }

        if ($request->filled('apk_release_notes')) {
            $generalSetting->apk_release_notes = $validated['apk_release_notes'];
        }

        // Handle Tablet APK file upload
        if ($request->hasFile('tablet_apk_file')) {
            // Delete old tablet APK file if exists
            if ($generalSetting->tablet_apk_file_path && Storage::disk('public')->exists($generalSetting->tablet_apk_file_path)) {
                Storage::disk('public')->delete($generalSetting->tablet_apk_file_path);
            }

            // Store new tablet APK file
            $file = $request->file('tablet_apk_file');
            $path = $file->store('apk/tablet', 'public');

            $generalSetting->tablet_apk_file_path = $path;
            $generalSetting->tablet_apk_file_size = $file->getSize();
        }

        // Update Tablet APK version and release notes
        if ($request->filled('tablet_apk_version')) {
            $generalSetting->tablet_apk_version = $validated['tablet_apk_version'];
        }

        if ($request->filled('tablet_apk_release_notes')) {
            $generalSetting->tablet_apk_release_notes = $validated['tablet_apk_release_notes'];
        }

        $generalSetting->save();

        return to_route('admin.general-settings.edit')->with('success', 'General settings updated successfully.');
    }
}
