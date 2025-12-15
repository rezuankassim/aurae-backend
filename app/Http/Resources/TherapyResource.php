<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TherapyResource extends BaseResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'music' => $this->music,
            'music_url' => $this->music_url,
            'configuration' => $this->configuration,
            'is_active' => $this->is_active,
            'is_custom' => $this->is_custom,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
