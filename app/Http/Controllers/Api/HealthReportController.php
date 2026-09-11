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
