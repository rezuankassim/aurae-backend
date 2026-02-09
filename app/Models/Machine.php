<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'last_used_at' => 'datetime',
        'last_logged_in_at' => 'datetime',
    ];

    /**
     * Get the user that owns this machine.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device (tablet) linked to this machine.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Check if machine is bound to a user.
     */
    public function isBound(): bool
    {
        return ! is_null($this->user_id);
    }

    /**
     * Check if machine is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }
}
