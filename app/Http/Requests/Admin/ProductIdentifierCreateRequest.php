<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductIdentifierCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:255'],
            'gtin' => ['nullable', 'string', 'max:255'],
            'mpn' => ['nullable', 'string', 'max:255'],
            'ean' => ['nullable', 'string', 'max:255'],
        ];
    }
}
