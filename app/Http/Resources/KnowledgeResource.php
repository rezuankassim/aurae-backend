<?php

namespace App\Http\Resources;

class KnowledgeResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'html_content' => $this->html_content,
            'video_url' => $this->video_url,
            'video_stream_url' => $this->video_path ? route('api.knowledge.video.stream', ['knowledge' => $this->id]) : null,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
