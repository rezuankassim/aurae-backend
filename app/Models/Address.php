<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_default',
        'type',
        'name',
        'phone',
        'line1',
        'line2',
        'line3',
        'city',
        'state',
        'postal_code',
        'country',
        'user_id',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
