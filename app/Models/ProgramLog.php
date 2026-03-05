<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'therapy_id',
        'program_duration',
        'action',
        'program_started_at',
        'program_ended_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'program_started_at' => 'datetime',
            'program_ended_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the program log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the therapy (program) associated with this log.
     */
    public function therapy()
    {
        return $this->belongsTo(Therapy::class);
    }
}
