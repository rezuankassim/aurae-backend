<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'user_id',
        'device_id',
        'maintenance_requested_at',
        'factory_maintenance_requested_at',
        'service_type',
        'requested_at_changes',
        'is_factory_approved',
        'is_user_approved',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_requested_at' => 'datetime',
            'factory_maintenance_requested_at' => 'datetime',
            'requested_at_changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
