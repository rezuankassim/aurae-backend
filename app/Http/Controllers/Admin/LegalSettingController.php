<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class LegalSettingController extends Controller
{
    public function edit()
    {
        $generalSetting = GeneralSetting::firstOrCreate();

        return Inertia::render('admin/legal-settings/edit', [
            'generalSetting' => $generalSetting,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'terms_and_conditions_content' => ['nullable', 'string'],
            'terms_and_conditions_html' => ['nullable', 'string'],
            'privacy_policy_content' => ['nullable', 'string'],
            'privacy_policy_html' => ['nullable', 'string'],
        ]);

        $generalSetting = GeneralSetting::first();
        $generalSetting->update($validated);

        Cache::forget('general_setting');

        return to_route('admin.legal-settings.edit')->with('success', 'Legal settings updated successfully.');
    }
}
