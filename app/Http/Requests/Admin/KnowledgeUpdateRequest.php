<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
            'content' => ['required', 'string'],
            'html_content' => ['required', 'string'],
            'published_date' => ['nullable', 'date'],
            'published_time' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],

        ];
    }
}
