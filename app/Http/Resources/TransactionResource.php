<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TransactionResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'driver' => $this->driver,
            'reference' => $this->reference,
            'amount' => $this->amount?->formatted,
            'status' => $this->status,
            'success' => $this->success,
            'card_type' => $this->card_type,
            'last_four' => $this->last_four,
            'notes' => $this->notes,
            'captured_at' => $this->captured_at instanceof \Carbon\Carbon ? $this->captured_at->toIso8601String() : $this->captured_at,
            'created_at' => $this->created_at instanceof \Carbon\Carbon ? $this->created_at->toIso8601String() : $this->created_at,
        ];
    }
}
