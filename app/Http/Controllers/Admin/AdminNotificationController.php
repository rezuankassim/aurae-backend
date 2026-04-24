<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminNotificationController extends Controller
{
    /**
     * Display all admin notifications in a paginated table.
     */
    public function index(Request $request)
    {
        $notifications = AdminNotification::orderByDesc('created_at')->get();

        return Inertia::render('admin/notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Display a single admin notification.
     */
    public function show(AdminNotification $notification)
    {
        $shippingAddress = null;
        $billingAddress = null;

        $userId = $notification->data['user_id'] ?? null;

        if ($userId) {
            $user = User::find($userId);

            if ($user) {
                $customer = $user->customers()->first();

                if ($customer) {
                    $addresses = $customer->addresses()->with('country')->get();

                    $shippingAddress = $addresses->firstWhere('shipping_default', true);
                    $billingAddress = $addresses->firstWhere('billing_default', true);
                }
            }
        }

        return Inertia::render('admin/notifications/show', [
            'notification' => $notification,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
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
