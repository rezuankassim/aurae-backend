<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'icon',
        'title',
        'pricing_title',
        'description',
        'max_devices',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_devices' => 'integer',
    ];

    /**
     * Get the user subscriptions for this plan.
     */
    public function userSubscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
