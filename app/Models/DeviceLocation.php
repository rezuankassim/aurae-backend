<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceLocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'altitude' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
    ];

    public function userDevice(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class);
    }

    public function getFormattedCoordinatesAttribute(): string
    {
        if (! $this->latitude || ! $this->longitude) {
            return 'N/A';
        }

        return sprintf('%s, %s', $this->latitude, $this->longitude);
    }

    public function getGoogleMapsUrlAttribute(): string
    {
        if (! $this->latitude || ! $this->longitude) {
            return '';
        }

        return sprintf('https://www.google.com/maps?q=%s,%s', $this->latitude, $this->longitude);
    }

    public function scopeForDevice(Builder $query, int $deviceId): Builder
    {
        return $query->where('user_device_id', $deviceId);
    }

    public function scopeDateRange(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
