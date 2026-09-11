<?php

namespace App\Http\Middleware;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'success' => fn () => $request->session()->get('success'),
            'error' => fn () => $request->session()->get('error'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'adminNotifications' => fn () => $request->user()?->is_admin
                ? AdminNotification::orderByDesc('created_at')->limit(5)->get()
                : null,
            'adminUnreadCount' => fn () => $request->user()?->is_admin
                ? AdminNotification::whereNull('read_at')->count()
                : 0,
        ];
    }
}
