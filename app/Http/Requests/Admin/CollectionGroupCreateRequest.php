<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CollectionGroupCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:lunar_collection_groups,name'],
            'handle' => ['required', 'string', 'max:255', 'unique:lunar_collection_groups,handle'],
        ];
    }
}
