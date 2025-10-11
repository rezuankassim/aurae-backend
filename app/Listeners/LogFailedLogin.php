<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        LoginActivity::create([
            'user_id'    => optional($event->user)->id, // may be null
            'event'      => 'failed',
            'guard'      => $event->guard ?? null,
            'session_id' => null,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'succeeded'  => false,
            'occurred_at'=> now(),
        ]);
    }
}
