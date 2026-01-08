<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceMaintenanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'maintenance_date' => ['required', 'date', 'after_or_equal:today'],
            'maintenance_time' => ['required', 'date_format:H:i:s'],
            'service_type' => ['required', 'in:Yearly service,Monthly service,One-time service'],
        ];
    }
}
