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
        'image',
        'name',
        'description',
        'music',
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
        return asset('storage/'.$this->music);
    }

    /**
     * Get the user that owns the therapy
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
