<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()->is_admin) {
            return $this->admin();
        }

        return Inertia::render('dashboard');
    }

    /**
     * Display the admin dashboard.
     */
    public function admin()
    {
        return Inertia::render('admin/dashboard/index', [
            'totalUsers' => User::where('is_admin', false)->count(),
            'totalDevices' => Device::count(),
            'onlineDevices' => Device::where('status', 1)->count(),
            'topSubscriptions' => $this->getTopSubscriptions(),
            'chartFilter' => $this->getChartFilter(),
        ]);
    }

    private function getChartFilter(): array
    {
        $dateFrom = request()->query('date_from');
        $dateTo = request()->query('date_to');

        return [
            'range' => ($dateFrom && $dateTo) ? 'custom' : request()->query('range', '90d'),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    private function getTopSubscriptions(): array
    {
        $filter = $this->getChartFilter();

        return Subscription::withCount(['userSubscriptions' => function ($q) use ($filter) {
            if ($filter['range'] === 'custom' && $filter['dateFrom'] && $filter['dateTo']) {
                $q->whereBetween('starts_at', [
                    Carbon::parse($filter['dateFrom'])->startOfDay(),
                    Carbon::parse($filter['dateTo'])->endOfDay(),
                ]);
            } else {
                $days = match ($filter['range']) {
                    '30d' => 30,
                    '7d' => 7,
                    default => 90,
                };
                $q->where('starts_at', '>=', Carbon::today()->subDays($days));
            }
        }])
            ->orderBy('user_subscriptions_count', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'name' => $s->title,
                'count' => $s->user_subscriptions_count,
            ])
            ->toArray();
    }
}
