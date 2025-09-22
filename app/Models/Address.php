<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'is_default',
        'type', // 0 = Home, 1 = Work, 2 = Other
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

    /**
     * Get the user that owns the address.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
