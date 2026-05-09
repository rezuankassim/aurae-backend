<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UsageHistoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $content = $this->content ?? null;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'therapy_id' => $this->therapy_id,
            'therapy' => $this->whenLoaded('therapy', function () {
                return [
                    'id' => $this->therapy?->id,
                    'name' => $this->therapy?->name,
                    'image_url' => $this->therapy?->image_url,
                ];
            }),
            'duration' => $content->duration ?? null,
            'force_stopped' => $content->force_stopped ?? false,
            'started_at' => $content->started_at ?? null,
            'ended_at' => $content->ended_at ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
