<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class HealthReportCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'full_body_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'meridian_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
            'multidimensional_file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
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
            'full_body_file.mimes' => 'Full body health report must be a PDF document.',
            'full_body_file.max' => 'Full body health report must not exceed 50MB.',
            'meridian_file.mimes' => 'Meridian health report must be a PDF document.',
            'meridian_file.max' => 'Meridian health report must not exceed 50MB.',
            'multidimensional_file.mimes' => 'Multidimensional health report must be a PDF document.',
            'multidimensional_file.max' => 'Multidimensional health report must not exceed 50MB.',
        ];
    }

}
