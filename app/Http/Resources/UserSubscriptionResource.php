<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserSubscriptionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->relationLoaded('user') ? $this->user : $request->user();

        $daysRemaining = null;
        if ($this->ends_at) {
            $daysRemaining = max(0, now()->diffInDays($this->ends_at, false));
        }

        // Get machine bound to this subscription
        $machine = $this->relationLoaded('machine') ? $this->machine : $this->machine()->first();

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
            'has_machine' => $machine !== null,
            'machine' => $machine ? [
                'id' => $machine->id,
                'serial_number' => $machine->serial_number,
                'name' => $machine->name,
                'status' => $machine->status,
                'thumbnail_url' => $machine->thumbnail_url,
                'detail_image_url' => $machine->detail_image_url,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
