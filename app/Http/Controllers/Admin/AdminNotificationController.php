<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    /**
     * Return the latest 5 admin notifications as JSON.
     */
    public function index(Request $request)
    {
        $notifications = AdminNotification::orderByDesc('created_at')
            ->limit(5)
            ->get();

        $unreadCount = AdminNotification::whereNull('read_at')->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(AdminNotification $notification)
    {
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
