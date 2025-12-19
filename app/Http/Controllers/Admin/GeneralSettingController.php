<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GeneralSettingUpdateRequest;
use App\Models\GeneralSetting;
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
        $generalSetting->save();

        return to_route('admin.general-settings.edit')->with('success', 'General settings updated successfully.');
    }
}
