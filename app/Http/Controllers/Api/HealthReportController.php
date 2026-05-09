<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\HealthReportResource;
use App\Models\Guest;
use App\Models\HealthReport;
use App\Support\OwnerDeviceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HealthReportController extends Controller
{
    /**
     * Determine if the request user may view this health report.
     *
     * Either the report belongs to them directly, OR the report belongs to
     * a guest user that is registered on a device the request user owns.
     */
    protected function userCanAccess(Request $request, HealthReport $healthReport): bool
    {
        $user = $request->user();

        if ($healthReport->user_id === $user->id) {
            return true;
        }

        $guest = Guest::where('user_id', $healthReport->user_id)->first();

        if (! $guest) {
            return false;
        }

        return OwnerDeviceResolver::ownsDevice($user, $guest->device_id);
    }

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
        if (! $this->userCanAccess($request, $healthReport)) {
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

    /**
     * Stream a specific report file.
     */
    public function file(Request $request, HealthReport $healthReport, string $type)
    {
        if (! $this->userCanAccess($request, $healthReport)) {
            return BaseResource::make([])
                ->additional([
                    'status' => 403,
                    'message' => 'You do not have permission to view this health report.',
                ])
                ->response()
                ->setStatusCode(403);
        }

        $fileField = match ($type) {
            'full_body' => 'full_body_file',
            'meridian' => 'meridian_file',
            'multidimensional' => 'multidimensional_file',
            default => null,
        };

        if (! $fileField) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => 'Invalid report type.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        $file = $healthReport->{$fileField};

        if (! $file || ! file_exists(storage_path('app/private/'.$file))) {
            return BaseResource::make([])
                ->additional([
                    'status' => 404,
                    'message' => 'Report file not found.',
                ])
                ->response()
                ->setStatusCode(404);
        }

        $response = response()->file(storage_path('app/private/'.$file), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::afterLast($file, '/').'"',
        ]);

        $response->setAutoEtag();
        $response->setAutoLastModified();
        $response->isNotModified($request);

        return $response;
    }
}
