<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TherapyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'image' => 'nullable|image|max:10240',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'music_id' => 'required|exists:music,id',
            'duration' => 'required|integer|min:1',
            'temp' => 'nullable|integer|min:0|max:100',
            'light' => 'nullable|in:on,off',
            'color_led' => 'required|string|in:Off,Red,Orange,Yellow,Green,Blue,Purple,White,Cyan',
            'status' => 'sometimes|boolean',
        ];
    }
}
