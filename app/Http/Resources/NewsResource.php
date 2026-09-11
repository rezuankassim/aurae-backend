<?php

namespace App\Http\Resources;

class NewsResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'image' => $this->image,
            'title' => $this->title,
            'content' => $this->content,
            'html_content' => $this->html_content,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
