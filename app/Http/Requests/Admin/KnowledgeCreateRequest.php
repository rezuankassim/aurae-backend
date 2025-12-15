<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeCreateRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'html_content' => ['required', 'string'],
            'published_date' => ['nullable', 'date'],
            'published_time' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,wmv,flv,mkv,webm', 'max:5242880'], // 5GB = 5242880 KB
            'video_path' => ['nullable', 'string', 'max:500'], // For chunked upload path
        ];
    }

    /**
     * Customize the error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'html_content.required' => 'The content field is required.',
        ];  
    }
}
