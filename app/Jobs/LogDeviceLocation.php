<?php

namespace App\Jobs;

use App\Models\DeviceLocation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LogDeviceLocation implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userDeviceId,
        public ?string $deviceId,
        public string $latitude,
        public string $longitude,
        public ?string $accuracy,
        public ?string $altitude,
        public ?string $speed,
        public ?string $heading,
        public string $apiEndpoint,
        public ?string $ipAddress,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DeviceLocation::create([
            'user_device_id' => $this->userDeviceId,
            'device_id' => $this->deviceId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'altitude' => $this->altitude,
            'speed' => $this->speed,
            'heading' => $this->heading,
            'api_endpoint' => $this->apiEndpoint,
            'ip_address' => $this->ipAddress,
        ]);
    }
}
