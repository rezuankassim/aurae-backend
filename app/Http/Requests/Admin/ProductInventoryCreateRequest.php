<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductInventoryCreateRequest extends FormRequest
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
            'stock' => ['nullable', 'integer', 'min:0'],
            'backorder' => ['nullable', 'integer', 'min:0'],
            'purchasable' => ['required', 'in:always,in_stock,in_stock_or_on_backorder'],
            'quantity_increment' => ['required', 'integer', 'min:1'],
            'min_quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
