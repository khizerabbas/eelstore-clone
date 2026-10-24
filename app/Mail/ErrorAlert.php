<?php

namespace App\Mail;

use App\Filament\Resources\ErrorLogs\ErrorLogResource;
use App\Models\ErrorLog;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

// "New error on the website" email to the owner (see ErrorRecorder::maybeAlert)
class ErrorAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public ErrorLog $error, public bool $reopened = false)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ ' . ($this->reopened ? 'Error is back' : 'New error') . ' on ' . SiteSetting::storeName() . ': '
                . Str::limit($this->error->message, 70),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.errors.alert',
            with: [
                'error'    => $this->error,
                'reopened' => $this->reopened,
                'url'      => ErrorLogResource::getUrl('index', ['tableAction' => 'view', 'tableActionRecord' => $this->error->id], panel: 'admin'),
            ],
        );
    }
}
