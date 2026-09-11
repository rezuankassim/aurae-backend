<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HealthReportCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
