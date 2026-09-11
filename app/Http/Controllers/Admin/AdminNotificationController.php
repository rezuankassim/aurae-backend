<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AdminNotification::orderByDesc('created_at')->get();

        return Inertia::render('admin/notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    public function show(AdminNotification $notification)
    {
        $shippingAddress = null;
        $billingAddress = null;
        $emergencyContacts = [];

        $userId = is_array($notification->data)
            ? ($notification->data['user_id'] ?? null)
            : null;

        if ($userId) {
            $user = User::find($userId);

            if ($user) {
                $customer = $user->customers()->first();

                if ($customer) {
                    $addresses = $customer->addresses()->with('country')->get();

                    $shippingAddress = $addresses->firstWhere('shipping_default', true);
                    $billingAddress = $addresses->firstWhere('billing_default', true);
                }

                $emergencyContacts = $user->emergencyContacts()
                    ->orderBy('created_at')
                    ->get();
            }
        }

        return Inertia::render('admin/notifications/show', [
            'notification' => $notification,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'emergencyContacts' => $emergencyContacts,
        ]);
    }

    public function markAsRead(AdminNotification $notification)
    {
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        AdminNotification::whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
