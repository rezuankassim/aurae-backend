<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogSuccessfulLogin
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
        LoginActivity::firstOrCreate(
            [
                'user_id'    => $event->user->id,
                'session_id' => request()->session()->getId(),
                'event' => 'login',
            ],
            [
                'guard'      => $event->guard ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
                'succeeded'  => true,
                'occurred_at'=> now(),
            ]
        );
    }
}
