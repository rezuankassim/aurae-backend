<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthReport extends Model
{
    /** @use HasFactory<\Database\Factories\HealthReportFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'file',
        'type',
        'user_id',
    ];

    /**
     * Get the user that owns the health report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
