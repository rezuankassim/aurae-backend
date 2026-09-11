<?php

namespace App\Support;

use App\Models\Device;
use App\Models\Machine;
use App\Models\User;

class OwnerDeviceResolver
{
    public static function deviceIdsFor(User $owner): array
    {
        $direct = Device::query()
            ->where('user_id', $owner->id)
            ->pluck('id');

        $viaMachine = Machine::query()
            ->where('user_id', $owner->id)
            ->whereNotNull('device_id')
            ->pluck('device_id');

        return $direct->merge($viaMachine)->unique()->values()->all();
    }

    public static function ownsDevice(User $owner, ?string $deviceId): bool
    {
        if (empty($deviceId)) {
            return false;
        }

        return in_array($deviceId, self::deviceIdsFor($owner), true);
    }
}
