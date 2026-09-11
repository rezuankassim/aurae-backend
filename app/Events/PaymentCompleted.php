<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $referenceNumber,
        public string $status,
        public int $orderId,
        public ?string $transactionId = null,
        public ?string $amount = null,
        public ?string $currency = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('payment.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'reference_number' => $this->referenceNumber,
            'status' => $this->status,
            'order_id' => $this->orderId,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
