<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceMaintenanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'maintenance_date' => ['required', 'date', 'after_or_equal:today'],
            'maintenance_time' => ['required', 'date_format:H:i:s'],
            'service_type' => ['required', 'in:Yearly service,Monthly service,One-time service'],
        ];
    }
}
