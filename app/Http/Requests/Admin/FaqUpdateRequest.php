<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FaqUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:0,1'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
        ];
    }
}
