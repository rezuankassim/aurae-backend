<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Therapy extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'music_id',
        'image',
        'name',
        'description',
        'music', // Kept for backward compatibility if needed, or to be removed later
        'configuration',
        'is_active',
        'is_custom',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    /**
     * Get the image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    /**
     * Get the music URL
     */
    public function getMusicUrlAttribute(): string
    {
        if ($this->music_id && $this->musicRelation) {
            return $this->musicRelation->url;
        }

        return $this->music ? asset('storage/'.$this->music) : '';
    }

    /**
     * Get the music associated with the therapy
     */
    public function musicRelation()
    {
        return $this->belongsTo(Music::class, 'music_id');
    }

    /**
     * Get the user that owns the therapy
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
