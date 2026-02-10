<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon',
        'title',
        'pricing_title',
        'description',
        'price',
        'is_active',
        'senangpay_recurring_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_machines' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Always set max_machines to 1 when creating
        static::creating(function ($subscription) {
            $subscription->max_machines = 1;
        });

        // Prevent updating max_machines
        static::updating(function ($subscription) {
            $subscription->max_machines = 1;
        });
    }

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
