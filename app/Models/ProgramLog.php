<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramLog extends Model
{
    protected $fillable = [
        'user_id',
        'therapy_id',
        'program_duration',
        'action',
        'program_started_at',
        'program_ended_at',
        'program_error_message',
        'emergency',
    ];

    protected function casts(): array
    {
        return [
            'program_started_at' => 'datetime',
            'program_ended_at' => 'datetime',
            'emergency' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function therapy()
    {
        return $this->belongsTo(Therapy::class);
    }
}
