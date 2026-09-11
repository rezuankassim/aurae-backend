<?php

namespace App\Listeners;

use App\Models\LoginActivity;

class LogFailedLogin
{
    public function __construct() {}

    public function handle(object $event): void
    {
        LoginActivity::create([
            'user_id' => optional($event->user)->id,
            'event' => 'failed',
            'guard' => $event->guard ?? null,
            'session_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'succeeded' => false,
            'occurred_at' => now(),
        ]);
    }
}
