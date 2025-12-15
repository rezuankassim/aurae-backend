<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageHistory extends Model
{
    /** @use HasFactory<\Database\Factories\UsageHistoryFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'therapy_id',
        'content',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'object',
        ];
    }

    /**
     * Get the user that owns the usage history
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the therapy that was used
     */
    public function therapy()
    {
        return $this->belongsTo(Therapy::class);
    }
}
