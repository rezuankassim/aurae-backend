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
        'content',
        'html_content',
        'video_url',
        'is_published',
        'published_at',
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
}
