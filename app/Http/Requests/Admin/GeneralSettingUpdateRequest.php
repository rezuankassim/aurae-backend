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
            'apk_file' => ['nullable', 'file', 'mimes:apk', 'max:102400'], // Max 100MB
            'apk_version' => ['nullable', 'string', 'max:255'],
            'apk_release_notes' => ['nullable', 'string'],
            'tablet_apk_file' => ['nullable', 'file', 'mimes:apk', 'max:102400'], // Max 100MB
            'tablet_apk_version' => ['nullable', 'string', 'max:255'],
            'tablet_apk_release_notes' => ['nullable', 'string'],
        ];
    }
}
