<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceMaintenanceCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'maintenance_date' => ['required', 'date', 'after_or_equal:today'],
            'maintenance_time' => ['required', 'date_format:H:i:s'],
            'service_type' => ['required', 'in:Yearly service,Monthly service,One-time service'],
        ];
    }
}
