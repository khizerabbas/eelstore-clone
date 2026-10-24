<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sent to the store admin when a customer submits an order request
class NewOrderRequest extends Mailable implements ShouldQueue
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
            subject: "New order request {$this->order->reference} – {$this->order->customer_name}",
            replyTo: $this->order->customer_email
                ? [new Address($this->order->customer_email, $this->order->customer_name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.admin-new-request',
            with: ['order' => $this->order->loadMissing('items')],
        );
    }
}
