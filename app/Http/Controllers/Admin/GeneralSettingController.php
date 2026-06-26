<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GeneralSettingUpdateRequest;
use App\Models\GeneralSetting;
use App\Services\MachineSerialService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GeneralSettingController extends Controller
{
    public function __construct(
        protected MachineSerialService $serialService
    ) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $generalSetting = GeneralSetting::firstOrCreate();
        $nextSerial = $this->serialService->generateNextSerialNumber();

        return Inertia::render('admin/general-settings/edit', [
            'generalSetting' => $generalSetting,
            'next_serial_preview' => $nextSerial,
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
            // Delete old APK file if it exists (check both disks for backward compatibility)
            if ($generalSetting->apk_file_path) {
                foreach (['local', 'public'] as $oldDisk) {
                    if (Storage::disk($oldDisk)->exists($generalSetting->apk_file_path)) {
                        Storage::disk($oldDisk)->delete($generalSetting->apk_file_path);
                    }
                }
            }

            // Store new APK file on the private disk so it is only reachable via signed URLs
            $file = $request->file('apk_file');
            $filename = time().'_'.$file->getClientOriginalName();
            $path = $file->storeAs('apk', $filename, 'local');

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
            // Delete old tablet APK file if it exists (check both disks for backward compatibility)
            if ($generalSetting->tablet_apk_file_path) {
                foreach (['local', 'public'] as $oldDisk) {
                    if (Storage::disk($oldDisk)->exists($generalSetting->tablet_apk_file_path)) {
                        Storage::disk($oldDisk)->delete($generalSetting->tablet_apk_file_path);
                    }
                }
            }

            // Store new tablet APK file on the private disk so it is only reachable via signed URLs
            $file = $request->file('tablet_apk_file');
            $filename = time().'_tablet_'.$file->getClientOriginalName();
            $path = $file->storeAs('apk/tablet', $filename, 'local');

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

        // Update machine serial format settings
        if ($request->filled('machine_serial_format')) {
            $generalSetting->machine_serial_format = $validated['machine_serial_format'];
        }

        if ($request->filled('machine_serial_prefix')) {
            $generalSetting->machine_serial_prefix = $validated['machine_serial_prefix'];
        }

        if ($request->filled('machine_serial_length')) {
            $generalSetting->machine_serial_length = $validated['machine_serial_length'];
        }

        $generalSetting->save();

        Cache::forget('general_setting');

        return to_route('admin.general-settings.edit')->with('success', 'General settings updated successfully.');
    }
}
