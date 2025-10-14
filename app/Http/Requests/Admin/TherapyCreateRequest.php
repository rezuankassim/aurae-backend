<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TherapyCreateRequest extends FormRequest
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
            'image' => 'nullable|image|max:10240', // Max 10MB
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'music' => 'required|file|mimetypes:audio/*|max:10240', // Max 10MB
            'duration' => 'required|integer|min:1',
            'temp' => 'nullable|integer|min:0|max:100',
            'light' => 'nullable|integer|min:0|max:100',
            'status' => 'sometimes|boolean',
        ];
    }
}
