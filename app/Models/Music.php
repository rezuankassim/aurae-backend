<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Music extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'thumbnail',
        'path',
        'duration',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration' => 'integer',
        ];
    }

    /**
     * Get the storage disk based on environment.
     */
    protected function storageDisk(): string
    {
        return app()->environment('production') ? 's3' : 'public';
    }

    /**
     * Get the music URL
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        $disk = $this->storageDisk();

        if ($disk === 'public') {
            return asset('storage/'.$this->path);
        }

        return Storage::disk('s3')->url($this->path);
    }

    /**
     * Get the thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        $disk = $this->storageDisk();

        if ($disk === 'public') {
            return asset('storage/'.$this->thumbnail);
        }

        return Storage::disk('s3')->url($this->thumbnail);
    }
}
