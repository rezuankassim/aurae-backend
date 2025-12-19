<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class DeviceResource extends BaseResource
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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'started_at' => optional($this->started_at)->format('Y-m-d'),
            'should_end_at' => optional($this->should_end_at)->format('Y-m-d'),
            'last_used_at' => $this->last_used_at,
            'last_logged_in_at' => $this->last_logged_in_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'token' => $this->when(isset($this->token), $this->token),
        ];
    }
}
