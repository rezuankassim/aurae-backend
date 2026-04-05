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

    /**
     * Create a new message instance.
     *
     * The constructor signature matches what Lunar's UpdatesOrderStatus trait
     * expects when instantiating mailer classes:
     *   new $mailerClass($record, $data['additional_content'])
     */
    public function __construct(
        public Order $order,
        public ?string $additionalContent = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $statusLabel = config('lunar.orders.statuses.'.$this->order->status.'.label', ucfirst($this->order->status));

        return new Envelope(
            subject: 'Order Update: '.$statusLabel.' – '.$this->order->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.orders.status-updated',
        );
    }
}
