<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GeneralSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'contact_no' => ['required', 'string', 'max:255'],
            'apk_file' => ['nullable', 'file', 'extensions:apk', 'max:512000'],
            'apk_version' => ['nullable', 'string', 'max:255', 'regex:/^\d+\.\d+\.\d+$/'],
            'apk_release_notes' => ['nullable', 'string'],
            'tablet_apk_file' => ['nullable', 'file', 'extensions:apk', 'max:512000'],
            'tablet_apk_version' => ['nullable', 'string', 'max:255', 'regex:/^\d+\.\d+\.\d+$/'],
            'tablet_apk_release_notes' => ['nullable', 'string'],
            'machine_serial_format' => ['nullable', 'string', 'max:255'],
            'machine_serial_prefix' => ['nullable', 'string', 'max:50'],
            'machine_serial_length' => ['nullable', 'integer', 'min:4', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'apk_version.regex' => 'The APK version must be in semantic versioning format (e.g., 1.0.0).',
            'tablet_apk_version.regex' => 'The tablet APK version must be in semantic versioning format (e.g., 1.0.0).',
        ];
    }
}
