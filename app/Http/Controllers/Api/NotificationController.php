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
}
