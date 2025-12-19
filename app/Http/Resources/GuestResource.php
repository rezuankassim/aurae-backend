<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuestResource extends JsonResource
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
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'customer_id' => $this->customer_id,
            'last_logged_in_at' => $this->last_logged_in_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => $this->whenLoaded('user'),
            'token' => $this->when(isset($this->token), $this->token),
        ];
    }
}
