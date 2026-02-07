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
        $healthReports = HealthReport::where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'full_body_file' => $report->full_body_file,
                    'full_body_file_url' => $report->full_body_file ? asset('storage/'.$report->full_body_file) : null,
                    'meridian_file' => $report->meridian_file,
                    'meridian_file_url' => $report->meridian_file ? asset('storage/'.$report->meridian_file) : null,
                    'multidimensional_file' => $report->multidimensional_file,
                    'multidimensional_file_url' => $report->multidimensional_file ? asset('storage/'.$report->multidimensional_file) : null,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ];
            });

        return Inertia::render('health-reports/index', [
            'healthReports' => $healthReports,
        ]);
    }

    /**
     * Display the specified resource (PDF file).
     */
    public function show(HealthReport $healthReport, string $type)
    {
        abort_if($healthReport->user_id !== auth()->id(), 403);

        $fileField = match ($type) {
            'full_body' => 'full_body_file',
            'meridian' => 'meridian_file',
            'multidimensional' => 'multidimensional_file',
            default => abort(404),
        };

        $file = $healthReport->{$fileField};

        abort_unless($file && file_exists(storage_path('app/private/'.$file)), 404);

        return response()->file(storage_path('app/private/'.$file), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::afterLast($file, '/').'"',
        ]);
    }
}
