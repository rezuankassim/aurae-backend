<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    protected $fillable = [
        'parent_transaction_id',
        'user_subscription_id',
        'success',
        'type',
        'driver',
        'amount',
        'reference',
        'status',
        'card_type',
        'last_four',
        'notes',
        'meta',
        'captured_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'amount' => 'decimal:2',
        'meta' => 'array',
        'captured_at' => 'datetime',
    ];

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTransaction::class, 'parent_transaction_id');
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class);
    }
}
