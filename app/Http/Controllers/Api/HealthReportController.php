<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\HealthReportResource;
use App\Models\HealthReport;
use Illuminate\Http\Request;

class HealthReportController extends Controller
{
    /**
     * Display a listing of health reports for authenticated user.
     */
    public function index(Request $request)
    {
        $healthReports = HealthReport::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return HealthReportResource::collection($healthReports)
            ->additional([
                'status' => 200,
                'message' => 'Health reports retrieved successfully.',
            ]);
    }

    /**
     * Display the specified health report.
     */
    public function show(Request $request, HealthReport $healthReport)
    {
        // Verify health report belongs to authenticated user
        if ($healthReport->user_id !== $request->user()->id) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not have permission to view this health report.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        return HealthReportResource::make($healthReport)
            ->additional([
                'status' => 200,
                'message' => 'Health report retrieved successfully.',
            ]);
    }
}
