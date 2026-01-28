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
                    'file' => $report->file,
                    'type' => $report->type,
                    'file_name' => Str::afterLast($report->file, '/'),
                    'file_url' => asset('storage/'.$report->file),
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
        $uploadedCount = 0;

        $fileTypes = [
            'full_body_file' => 'full_body',
            'meridian_file' => 'meridian',
            'multidimensional_file' => 'multidimensional',
        ];

        foreach ($fileTypes as $fileKey => $type) {
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $path = $file->store('health-reports');

                HealthReport::create([
                    'file' => $path,
                    'type' => $type,
                    'user_id' => $validated['user_id'],
                ]);

                $uploadedCount++;
            }
        }

        $message = $uploadedCount > 1 ? "$uploadedCount health reports uploaded successfully." : 'Health report uploaded successfully.';

        return redirect()->route('admin.health-reports.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(HealthReport $healthReport)
    {
        abort_unless($healthReport->file && file_exists(storage_path('app/private/'.$healthReport->file)), 404);

        // stream pdf file
        return response()->file(storage_path('app/private/'.$healthReport->file), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::afterLast($healthReport->file, '/').'"',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HealthReport $healthReport)
    {
        // Delete file from storage
        if ($healthReport->file && file_exists(storage_path('app/private/'.$healthReport->file))) {
            unlink(storage_path('app/private/'.$healthReport->file));
        }

        $healthReport->delete();

        return redirect()->route('admin.health-reports.index')->with('success', 'Health report deleted successfully.');
    }
}
