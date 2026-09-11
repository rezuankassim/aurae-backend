<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'therapy_id',
        'content',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'object',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function therapy()
    {
        return $this->belongsTo(Therapy::class);
    }
}
