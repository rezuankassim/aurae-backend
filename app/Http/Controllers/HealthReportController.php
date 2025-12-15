<?php

namespace App\Http\Controllers;

use App\Http\Requests\HealthReportCreateRequest;
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('health-reports/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HealthReportCreateRequest $request)
    {
        $path = $request->file('file')->store('health-reports');

        HealthReport::create([
            'file' => $path,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('health-reports.index')->with('success', 'Health report uploaded successfully.');
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
