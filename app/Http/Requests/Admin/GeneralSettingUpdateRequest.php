<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_no' => ['required', 'string', 'max:255'],
            'apk_file' => ['nullable', 'file', 'extensions:apk', 'max:512000'], // Max 500MB
            'apk_version' => ['nullable', 'string', 'max:255', 'regex:/^\d+\.\d+\.\d+$/'],
            'apk_release_notes' => ['nullable', 'string'],
            'tablet_apk_file' => ['nullable', 'file', 'extensions:apk', 'max:512000'], // Max 500MB
            'tablet_apk_version' => ['nullable', 'string', 'max:255', 'regex:/^\d+\.\d+\.\d+$/'],
            'tablet_apk_release_notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'apk_version.regex' => 'The APK version must be in semantic versioning format (e.g., 1.0.0).',
            'tablet_apk_version.regex' => 'The tablet APK version must be in semantic versioning format (e.g., 1.0.0).',
        ];
    }
}
