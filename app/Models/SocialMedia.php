<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    protected $fillable = [
        'links',
    ];

    protected function casts(): array
    {
        return [
            'links' => 'array',
        ];
    }
}
