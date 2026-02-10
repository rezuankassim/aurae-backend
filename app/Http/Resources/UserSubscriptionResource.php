<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->relationLoaded('user') ? $this->user : $request->user();
        $currentMachineCount = $user ? $user->machines()->count() : 0;
        $maxMachines = $this->subscription?->max_machines ?? 1;

        $daysRemaining = null;
        if ($this->ends_at) {
            $daysRemaining = max(0, now()->diffInDays($this->ends_at, false));
        }

        return [
            'id' => $this->id,
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at,
            'transaction_id' => $this->transaction_id,
            'is_recurring' => $this->is_recurring,
            'next_billing_at' => $this->next_billing_at,
            'cancelled_at' => $this->cancelled_at,
            'days_remaining' => $daysRemaining,
            'current_machine_count' => $currentMachineCount,
            'max_machines' => $maxMachines,
            'machines_available' => max(0, $maxMachines - $currentMachineCount),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
