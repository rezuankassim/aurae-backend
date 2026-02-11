<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Machine extends Model
{
    use HasUlids;

    protected $guarded = [];

    protected $casts = [
        'last_used_at' => 'datetime',
        'last_logged_in_at' => 'datetime',
    ];

    protected $appends = ['thumbnail_url', 'detail_image_url'];

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
     * Get the user subscription this machine is bound to.
     */
    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
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

    /**
     * Get the thumbnail URL.
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail ? Storage::disk('s3')->url($this->thumbnail) : null,
        );
    }

    /**
     * Get the detail image URL.
     */
    protected function detailImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail_image ? Storage::disk('s3')->url($this->detail_image) : null,
        );
    }
}
