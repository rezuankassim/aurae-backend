<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('is_sent', true)
            ->orderBy('sent_at', 'desc')
            ->get();

        return NotificationResource::collection($notifications)
            ->additional([
                'status' => 200,
                'message' => 'Notifications retrieved successfully.',
            ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $notification->update(['read_at' => now()]);

        return NotificationResource::make($notification)
            ->additional([
                'status' => 200,
                'message' => 'Notification marked as read.',
            ]);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_sent', true)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 200,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
