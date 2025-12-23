<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DeviceMaintenanceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'user_id' => $this->user_id,
            'device_id' => $this->device_id,
            'device' => DeviceResource::make($this->whenLoaded('device')),
            'user' => $this->when($this->relationLoaded('user'), [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'maintenance_requested_at' => optional($this->maintenance_requested_at)->format('Y-m-d H:i:s'),
            'factory_maintenance_requested_at' => optional($this->factory_maintenance_requested_at)->format('Y-m-d H:i:s'),
            'is_factory_approved' => $this->is_factory_approved,
            'is_user_approved' => $this->is_user_approved,
            'requested_at_changes' => $this->requested_at_changes,
            'created_at' => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get the status label based on status code.
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            0 => 'Pending',
            1 => 'Pending Factory',
            2 => 'In Progress',
            3 => 'Completed',
            default => 'Unknown',
        };
    }
}
