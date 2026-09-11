<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Inertia\Inertia;

class LegalController extends Controller
{
    public function termsAndConditions()
    {
        $generalSetting = GeneralSetting::first();

        return Inertia::render('legal/terms-and-conditions', [
            'content' => $generalSetting?->terms_and_conditions_html ?? '',
        ]);
    }

    public function privacyPolicy()
    {
        $generalSetting = GeneralSetting::first();

        return Inertia::render('legal/privacy-policy', [
            'content' => $generalSetting?->privacy_policy_html ?? '',
        ]);
    }
}
