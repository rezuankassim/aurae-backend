<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * Get the machine linked to this device.
     */
    public function machine(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Machine::class);
    }
}
