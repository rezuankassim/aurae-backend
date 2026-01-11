<?php

namespace App\Http\Controllers;

use App\Models\HealthReport;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HealthReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $healthReports = HealthReport::where('user_id', auth()->id())->latest()->get();

        $healthReports->transform(function ($report) {
            $report->file_url = asset('storage/'.$report->file);

            return $report;
        });

        return Inertia::render('health-reports/index', [
            'healthReports' => $healthReports,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(HealthReport $healthReport)
    {
        abort_if($healthReport->user_id !== auth()->id(), 403);

        abort_unless($healthReport->file && file_exists(storage_path('app/private/'.$healthReport->file)), 404);

        // stream pdf file
        return response()->file(storage_path('app/private/'.$healthReport->file), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::afterLast($healthReport->file, '/').'"',
        ]);
    }
}
