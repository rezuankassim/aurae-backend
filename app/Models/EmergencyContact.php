<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'phone',
    ];

    /**
     * Get the user that owns this emergency contact.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
