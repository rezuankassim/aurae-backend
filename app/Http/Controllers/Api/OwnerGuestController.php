<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Http\Resources\GuestResource;
use App\Http\Resources\HealthReportResource;
use App\Http\Resources\UsageHistoryResource;
use App\Models\Device;
use App\Models\Guest;
use App\Models\HealthReport;
use App\Models\UsageHistory;
use App\Support\OwnerDeviceResolver;
use Illuminate\Http\Request;

class OwnerGuestController extends Controller
{
    /**
     * List all guests across every tablet the authenticated owner has connected.
     */
    public function index(Request $request)
    {
        $request->validate([
            'device_uuid' => ['nullable', 'string', 'exists:devices,uuid'],
        ]);

        $owner = $request->user();
        $deviceIds = OwnerDeviceResolver::deviceIdsFor($owner);

        if (empty($deviceIds)) {
            return GuestResource::collection(collect())
                ->additional([
                    'status' => 200,
                    'message' => 'Guests retrieved successfully.',
                ]);
        }

        // Optional filter to a single device the owner has access to.
        if ($request->filled('device_uuid')) {
            $filteredDeviceId = Device::where('uuid', $request->device_uuid)
                ->whereIn('id', $deviceIds)
                ->value('id');

            if (! $filteredDeviceId) {
                return BaseResource::make([])
                    ->additional([
                        'status' => 403,
                        'message' => 'You do not have access to this device.',
                    ])
                    ->response()
                    ->setStatusCode(403);
            }

            $deviceIds = [$filteredDeviceId];
        }

        $guests = Guest::query()
            ->whereIn('device_id', $deviceIds)
            ->whereHas('user')
            ->with(['user', 'device:id,uuid,name'])
            ->orderByDesc('created_at')
            ->get();

        return GuestResource::collection($guests)
            ->additional([
                'status' => 200,
                'message' => 'Guests retrieved successfully.',
            ]);
    }

    /**
     * List a guest's therapy usage history.
     */
    public function usageHistories(Request $request, Guest $guest)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'therapy_id' => ['nullable', 'exists:therapies,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if (! $this->ownerCanAccessGuest($request, $guest)) {
            return $this->forbidden();
        }

        $query = UsageHistory::query()
            ->where('user_id', $guest->user_id)
            ->with('therapy');

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if ($request->filled('therapy_id')) {
            $query->where('therapy_id', $request->input('therapy_id'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $usageHistories = $query->latest()->paginate($perPage);

        return UsageHistoryResource::collection($usageHistories)
            ->additional([
                'status' => 200,
                'message' => 'Usage histories retrieved successfully.',
            ]);
    }

    /**
     * List a guest's health reports.
     */
    public function healthReports(Request $request, Guest $guest)
    {
        if (! $this->ownerCanAccessGuest($request, $guest)) {
            return $this->forbidden();
        }

        $reports = HealthReport::query()
            ->where('user_id', $guest->user_id)
            ->latest()
            ->get();

        return HealthReportResource::collection($reports)
            ->additional([
                'status' => 200,
                'message' => 'Health reports retrieved successfully.',
            ]);
    }

    /**
     * Determine if the auth user owns the device the guest belongs to.
     */
    protected function ownerCanAccessGuest(Request $request, Guest $guest): bool
    {
        return OwnerDeviceResolver::ownsDevice($request->user(), $guest->device_id);
    }

    protected function forbidden()
    {
        return BaseResource::make([])
            ->additional([
                'status' => 403,
                'message' => 'You do not have permission to access this guest.',
            ])
            ->response()
            ->setStatusCode(403);
    }
}
