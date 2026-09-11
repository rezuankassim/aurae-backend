<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceMaintenanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'maintenance_requested_at' => ['required', 'date', 'after_or_equal:today'],
            'service_type' => ['required', 'in:Yearly service,Monthly service,One-time service'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => 'Please select a device.',
            'device_id.exists' => 'The selected device does not exist.',
            'maintenance_requested_at.required' => 'Please select a maintenance date and time.',
            'maintenance_requested_at.after_or_equal' => 'Maintenance date must be today or in the future.',
        ];
    }
}
