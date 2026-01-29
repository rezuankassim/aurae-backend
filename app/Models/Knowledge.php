<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Knowledge extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'cover_image',
        'content',
        'html_content',
        'video_url',
        'video_path',
        'is_published',
        'published_at',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the cover image URL
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/'.$this->cover_image) : null;
    }
}
