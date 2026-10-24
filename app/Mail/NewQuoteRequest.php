<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sent to the store admin for every bulk quote request (with the customer's list file attached)
class NewQuoteRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public QuoteRequest $quote)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bulk quote request {$this->quote->reference} – {$this->quote->name}",
            replyTo: $this->quote->email ? [new Address($this->quote->email, $this->quote->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quotes.admin-new-request',
            with: ['quote' => $this->quote, 'storeName' => SiteSetting::storeName()],
        );
    }

    public function attachments(): array
    {
        if (! $this->quote->attachment_path) {
            return [];
        }

        return [
            Attachment::fromStorageDisk(QuoteRequest::ATTACHMENT_DISK, $this->quote->attachment_path)
                ->as($this->quote->attachment_name ?: basename($this->quote->attachment_path)),
        ];
    }
}
