<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NewsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'image' => ['nullable', 'image', 'max:10280'],
            'type' => ['required', 'integer', 'in:0,1'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'html_content' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,unpublished'],
            'published_date' => ['nullable', 'date_format:d-m-Y'],
            'published_time' => ['nullable', 'date_format:H:i:s'],
        ];
    }
}
