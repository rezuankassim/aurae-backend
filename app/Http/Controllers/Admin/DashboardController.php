<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $subscriptionChartData = $this->getSubscriptionChartData();

        return Inertia::render('admin/dashboard/index', [
            'totalUsers' => User::where('is_admin', false)->count(),
            'totalDevices' => Device::count(),
            'onlineDevices' => Device::where('status', 1)->count(),
            'subscriptionChartData' => $subscriptionChartData,
        ]);
    }

    private function getSubscriptionChartData(): array
    {
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays(89);

        $newPerDay = UserSubscription::selectRaw('DATE(starts_at) as date, COUNT(*) as count')
            ->whereBetween('starts_at', [$startDate, $endDate->copy()->endOfDay()])
            ->groupBy('date')
            ->pluck('count', 'date');

        $activePerDay = UserSubscription::selectRaw('DATE(starts_at) as date, COUNT(*) as count')
            ->where('status', 'active')
            ->whereBetween('starts_at', [$startDate, $endDate->copy()->endOfDay()])
            ->groupBy('date')
            ->pluck('count', 'date');

        $data = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $key = $date->toDateString();
            $data[] = [
                'date' => $key,
                'new' => $newPerDay[$key] ?? 0,
                'active' => $activePerDay[$key] ?? 0,
            ];
        }

        return $data;
    }
}
