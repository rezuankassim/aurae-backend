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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }

    public function isBound(): bool
    {
        return ! is_null($this->user_id);
    }

    public function isActive(): bool
    {
        return $this->status === 1;
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail ? Storage::disk('s3')->url($this->thumbnail) : null,
        );
    }

    protected function detailImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->detail_image ? Storage::disk('s3')->url($this->detail_image) : null,
        );
    }
}
