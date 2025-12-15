<?php

namespace App\Listeners;

use App\Models\LoginActivity;

class LogLogout
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
        $sessionId = request()->session()->getId();

        // Attach logout time to the matching login for this session (optional but nice)
        LoginActivity::where('user_id', optional($event->user)->id)
            ->where('session_id', $sessionId)
            ->where('event', 'login')
            ->latest('occurred_at')
            ->limit(1)
            ->update(['logout_at' => now()]);

        // Also record a logout event row (optional)
        LoginActivity::create([
            'user_id' => optional($event->user)->id,
            'event' => 'logout',
            'guard' => $event->guard ?? null,
            'session_id' => $sessionId,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 500),
            'succeeded' => true,
            'occurred_at' => now(),
        ]);
    }
}
