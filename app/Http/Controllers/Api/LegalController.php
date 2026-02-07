<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\GeneralSetting;

class LegalController extends Controller
{
    /**
     * Get the terms and conditions.
     */
    public function termsAndConditions()
    {
        $generalSetting = GeneralSetting::first();

        return BaseResource::make([
            'content' => $generalSetting?->terms_and_conditions_html ?? '',
        ])->additional([
            'status' => 200,
            'message' => 'Terms and conditions retrieved successfully.',
        ]);
    }

    /**
     * Get the privacy policy.
     */
    public function privacyPolicy()
    {
        $generalSetting = GeneralSetting::first();

        return BaseResource::make([
            'content' => $generalSetting?->privacy_policy_html ?? '',
        ])->additional([
            'status' => 200,
            'message' => 'Privacy policy retrieved successfully.',
        ]);
    }
}
