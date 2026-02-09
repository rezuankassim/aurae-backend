<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MachineResource extends JsonResource
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
            'serial_number_masked' => $this->maskSerialNumber($this->serial_number),
            'serial_number' => $this->when($request->user()?->is_admin, $this->serial_number),
            'name' => $this->name,
            'status' => $this->status,
            'status_text' => $this->status === 1 ? 'Active' : 'Inactive',
            'is_bound' => $this->isBound(),
            'last_used_at' => $this->last_used_at,
            'last_logged_in_at' => $this->last_logged_in_at,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'device' => $this->whenLoaded('device', function () {
                return [
                    'id' => $this->device->id,
                    'name' => $this->device->name,
                    'uuid' => $this->device->uuid,
                ];
            }),
        ];
    }

    /**
     * Mask serial number for security (show last 4 digits).
     */
    protected function maskSerialNumber(string $serialNumber): string
    {
        if (strlen($serialNumber) <= 4) {
            return $serialNumber;
        }

        return str_repeat('X', strlen($serialNumber) - 4).substr($serialNumber, -4);
    }
}
