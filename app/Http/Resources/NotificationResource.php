<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class NotificationResource extends BaseResource
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
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'type' => $this->type,
            'sent_at' => $this->sent_at,
            'is_sent' => $this->is_sent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
