<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'guard',
        'session_id',
        'ip_address',
        'user_agent',
        'occurred_at',
        'logout_at',
        'succeeded',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'logout_at' => 'datetime',
        'succeeded' => 'boolean',
    ];

    /**
     * Get the user that owns the login activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Duration (in seconds) between login and logout for one session row (if available)
    public function sessionDuration(): ?int
    {
        if ($this->event !== 'login' || ! $this->logout_at) {
            return null;
        }

        return $this->logout_at->diffInSeconds($this->occurred_at);
    }

    // Quick check if the login session (this row) is still active
    public function isActive(): bool
    {
        return $this->event === 'login' && is_null($this->logout_at);
    }
}
