<?php

namespace App\Mail\Orders;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\Order;

class OrderStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public ?string $additionalContent = null,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = config('lunar.orders.statuses.'.$this->order->status.'.label', ucfirst($this->order->status));

        return new Envelope(
            subject: 'Order Update: '.$statusLabel.' – '.$this->order->reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.status-updated',
        );
    }
}
