<?php

namespace App\Http\Middleware;

use App\Models\LoginActivity;
use Closure;

class HandleLoginSessionIdLogger
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (! auth()->check()) {
            return;
        } // only if user is now logged in

        $finalSid = $request->session()->getId();

        // Update the most recent login row for this user that has a different (pre-rotation) SID
        LoginActivity::where('user_id', auth()->id())
            ->where('event', 'login')
            ->whereNull('logout_at')
            ->where('session_id', '!=', $finalSid)
            ->latest('occurred_at')
            ->limit(1)
            ->update(['session_id' => $finalSid]);
    }
}
