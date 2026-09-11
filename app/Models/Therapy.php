<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Therapy extends Model
{
    protected $fillable = [
        'user_id',
        'music_id',
        'image',
        'name',
        'description',
        'music',
        'configuration',
        'is_active',
        'is_custom',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'is_active' => 'boolean',
            'is_custom' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function getMusicUrlAttribute(): string
    {
        if ($this->music_id && $this->musicRelation) {
            return $this->musicRelation->url;
        }

        return $this->music ? asset('storage/'.$this->music) : '';
    }

    public function musicRelation()
    {
        return $this->belongsTo(Music::class, 'music_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function programLogs()
    {
        return $this->hasMany(ProgramLog::class);
    }
}
