<?php

namespace App\Jobs;

use App\Models\QuoteRequest;
use App\Services\WhatsApp\WhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendQuoteWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public QuoteRequest $quote)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(WhatsAppNotifier $whatsApp): void
    {
        if ($this->quote->owner_whatsapp_sent_at) {
            return;
        }

        $whatsApp->sendToOwner(self::message($this->quote));

        $this->quote->forceFill(['owner_whatsapp_sent_at' => now()])->saveQuietly();
    }

    public static function message(QuoteRequest $quote): string
    {
        $lines = [
            "📋 New bulk quote request {$quote->reference}",
            '',
            "Name: {$quote->name}",
            'Phone: ' . $quote->displayPhone(),
        ];

        foreach (['business_name' => 'Business', 'city' => 'City', 'email' => 'Email'] as $field => $label) {
            if ($quote->{$field}) {
                $lines[] = "{$label}: {$quote->{$field}}";
            }
        }

        if ($quote->items) {
            $lines[] = '';
            $lines[] = 'Items: ' . Str::limit(preg_replace('/\s*\n\s*/', '; ', $quote->items), 600);
        }

        if ($quote->cart_items) {
            $lines[] = 'From cart: ' . collect($quote->cart_items)->map(fn ($i) => "{$i['quantity']} x {$i['product_name']}")->implode(', ');
        }

        if ($quote->attachment_path) {
            $lines[] = '📎 List attached – see the email or admin panel';
        }

        $lines[] = '';
        $lines[] = 'Chat with customer: ' . $quote->whatsappUrl();

        return implode("\n", $lines);
    }
}
