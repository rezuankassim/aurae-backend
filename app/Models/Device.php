<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'date',
        'should_end_at' => 'date',
        'last_used_at' => 'datetime',
        'last_logged_in_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
