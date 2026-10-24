<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Confirmation to the customer (only when they gave an email), with their private status link
class OrderRequestReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We received your order request {$this->order->reference}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.customer-received',
            with: ['order' => $this->order->loadMissing('items')],
        );
    }
}
