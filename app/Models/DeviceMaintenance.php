<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMaintenance extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceMaintenanceFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'status', // 0: pending, 1: pending_factory, 2: in_progress, 3: completed
        'user_id',
        'device_id',
        'maintenance_requested_at',
        'factory_maintenance_requested_at',
        'requested_at_changes',
        'is_factory_approved',
        'is_user_approved',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_requested_at' => 'datetime',
            'factory_maintenance_requested_at' => 'datetime',
            'requested_at_changes' => 'array',
        ];
    }

    /**
     * Get the user that owns the device maintenance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device that needs maintenance.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
