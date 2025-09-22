<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NewsCreateRequest extends FormRequest
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
            'image' => ['nullable', 'image', 'max:10280'], // Max 10MB
            'type' => ['required', 'integer', 'in:0,1'], // 0: News, 1: Promotion
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'published_date' => ['nullable', 'date_format:d-m-Y'],
            'published_time' => ['nullable', 'date_format:H:i:s'],
        ];
    }
}
