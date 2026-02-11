<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeviceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Load machine with its subscription if not already loaded
        $machine = $this->relationLoaded('machine') ? $this->machine : $this->machine()->with('userSubscription.subscription')->first();

        // Get user's active subscriptions
        $user = $request->user();
        $subscriptions = $user ? $user->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->with(['subscription', 'machine'])
            ->get() : collect();

        return [
            'id' => $this->id,
            'status' => $this->status,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'thumbnail' => $this->thumbnail && Storage::exists($this->thumbnail)
                ? Storage::url($this->thumbnail)
                : null,
            'device_plan' => $this->device_plan,
            'started_at' => optional($this->started_at)->format('Y-m-d'),
            'should_end_at' => optional($this->should_end_at)->format('Y-m-d'),
            'last_used_at' => $this->last_used_at,
            'last_logged_in_at' => $this->last_logged_in_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'token' => $this->when(isset($this->token), $this->token),
            'machine' => $machine ? [
                'id' => $machine->id,
                'serial_number' => $machine->serial_number,
                'name' => $machine->name,
                'status' => $machine->status,
                'thumbnail_url' => $machine->thumbnail_url,
                'detail_image_url' => $machine->detail_image_url,
                'user_subscription_id' => $machine->user_subscription_id,
                'subscription' => $machine->userSubscription?->subscription ? [
                    'id' => $machine->userSubscription->subscription->id,
                    'title' => $machine->userSubscription->subscription->title,
                ] : null,
            ] : null,
            'subscriptions' => UserSubscriptionResource::collection($subscriptions),
        ];
    }
}
