<?php

namespace App\Models;

use Lunar\Models\Discount as LunarDiscount;

class Discount extends LunarDiscount
{
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
