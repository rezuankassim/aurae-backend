<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Models\UsageHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageHistoryController extends Controller
{
    /**
     * Store a newly created usage history in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'therapy_id' => ['required', 'exists:therapies,id'],
            'duration' => ['nullable', 'numeric', 'min:0'],
            'force_stopped' => ['nullable', 'boolean'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
        ]);

        $content = [
            'duration' => $request->input('duration'),
            'force_stopped' => $request->input('force_stopped', false),
            'started_at' => $request->input('started_at'),
            'ended_at' => $request->input('ended_at'),
        ];

        $usageHistory = UsageHistory::create([
            'user_id' => $request->user()->id,
            'therapy_id' => $request->input('therapy_id'),
            'content' => $content,
        ]);

        return BaseResource::make($usageHistory)
            ->additional([
                'status' => 201,
                'message' => 'Usage history created successfully.',
            ]);
    }

    /**
     * Get chart data for usage statistics.
     */
    public function chart(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $userId = $request->user()->id;
        $from = $request->input('from');
        $to = $request->input('to');

        // Build base query
        $query = UsageHistory::where('user_id', $userId);

        // Apply date filters if provided
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Get daily usage data
        $dailyUsage = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as sessions'),
                DB::raw('COALESCE(SUM(CAST(JSON_EXTRACT(content, "$.duration") AS DECIMAL(10,2))), 0) as total_duration')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'sessions' => (int) $item->sessions,
                    'total_duration' => (float) $item->total_duration,
                ];
            });

        // Calculate overall average daily usage
        $totalSessions = $query->count();
        $totalDuration = $query->sum(DB::raw('CAST(JSON_EXTRACT(content, "$.duration") AS DECIMAL(10,2))')) ?? 0;

        // Calculate number of days with usage
        $daysWithUsage = (clone $query)
            ->select(DB::raw('COUNT(DISTINCT DATE(created_at)) as days'))
            ->value('days') ?? 1;

        $averageDailySessions = $totalSessions / max($daysWithUsage, 1);
        $averageDailyDuration = $totalDuration / max($daysWithUsage, 1);

        return BaseResource::make([
            'daily_usage' => $dailyUsage,
            'daily_average' => [
                'average_sessions' => round($averageDailySessions, 2),
                'average_duration' => round($averageDailyDuration, 2),
                'total_sessions' => $totalSessions,
                'total_duration' => round($totalDuration, 2),
                'days_with_usage' => $daysWithUsage,
            ],
        ])
            ->additional([
                'status' => 200,
                'message' => 'Chart data retrieved successfully.',
            ]);
    }
}
