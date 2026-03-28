<?php

namespace App\Models;

use Illuminate\Support\Str;
use Lunar\Models\Discount as LunarDiscount;

class Discount extends LunarDiscount
{
    protected static function booted(): void
    {
        static::creating(function (Discount $discount) {
            if (empty($discount->handle)) {
                $discount->handle = Str::slug($discount->name);
            }
        });
    }
    const DRAFT = 'draft';

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'data' => 'array',
        'enabled' => 'boolean',
        'coupon' => \Lunar\Base\Casts\CouponString::class,
    ];

    public function getStatusAttribute(): string
    {
        if (! $this->enabled) {
            return static::DRAFT;
        }

        return parent::getStatusAttribute();
    }
}
