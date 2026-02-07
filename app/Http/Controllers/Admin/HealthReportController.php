<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HealthReportCreateRequest;
use App\Models\HealthReport;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HealthReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $healthReports = HealthReport::with('user:id,name,email')
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'full_body_file' => $report->full_body_file,
                    'full_body_file_name' => $report->full_body_file ? Str::afterLast($report->full_body_file, '/') : null,
                    'full_body_file_url' => $report->full_body_file ? asset('storage/'.$report->full_body_file) : null,
                    'meridian_file' => $report->meridian_file,
                    'meridian_file_name' => $report->meridian_file ? Str::afterLast($report->meridian_file, '/') : null,
                    'meridian_file_url' => $report->meridian_file ? asset('storage/'.$report->meridian_file) : null,
                    'multidimensional_file' => $report->multidimensional_file,
                    'multidimensional_file_name' => $report->multidimensional_file ? Str::afterLast($report->multidimensional_file, '/') : null,
                    'multidimensional_file_url' => $report->multidimensional_file ? asset('storage/'.$report->multidimensional_file) : null,
                    'user' => $report->user,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ];
            });

        return Inertia::render('admin/health-reports/index', [
            'healthReports' => $healthReports,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::query()
            ->where('is_admin', false)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/health-reports/create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HealthReportCreateRequest $request)
    {
        $validated = $request->validated();

        $data = [
            'user_id' => $validated['user_id'],
        ];

        if ($request->hasFile('full_body_file')) {
            $data['full_body_file'] = $request->file('full_body_file')->store('health-reports');
        }

        if ($request->hasFile('meridian_file')) {
            $data['meridian_file'] = $request->file('meridian_file')->store('health-reports');
        }

        if ($request->hasFile('multidimensional_file')) {
            $data['multidimensional_file'] = $request->file('multidimensional_file')->store('health-reports');
        }

        HealthReport::create($data);

        return redirect()->route('admin.health-reports.index')->with('success', 'Health report uploaded successfully.');
    }

    /**
     * Display the specified resource (PDF file).
     */
    public function show(HealthReport $healthReport, string $type)
    {
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HealthReport $healthReport)
    {
        // Delete files from storage
        $fileFields = ['full_body_file', 'meridian_file', 'multidimensional_file'];

        foreach ($fileFields as $field) {
            if ($healthReport->{$field} && file_exists(storage_path('app/private/'.$healthReport->{$field}))) {
                unlink(storage_path('app/private/'.$healthReport->{$field}));
            }
        }

        $healthReport->delete();

        return redirect()->route('admin.health-reports.index')->with('success', 'Health report deleted successfully.');
    }
}
