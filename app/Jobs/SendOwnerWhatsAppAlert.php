<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsApp\WhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOwnerWhatsAppAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(WhatsAppNotifier $whatsApp): void
    {
        if ($this->order->owner_whatsapp_sent_at) {
            return; // already sent (job retried after success)
        }

        $whatsApp->sendToOwner(self::message($this->order));

        $this->order->forceFill(['owner_whatsapp_sent_at' => now()])->saveQuietly();
    }

    public static function message(Order $order): string
    {
        $order->loadMissing('items');

        $lines = [
            "🛒 New order request {$order->reference}",
            '',
            "Name: {$order->customer_name}",
            'Phone: ' . $order->displayPhone(),
        ];

        if ($order->customer_email) {
            $lines[] = "Email: {$order->customer_email}";
        }

        if ($order->customer_address) {
            $lines[] = 'Address: ' . str_replace(["\r", "\n"], ' ', $order->customer_address);
        }

        $lines[] = '';
        $lines[] = 'Items:';

        foreach ($order->items as $item) {
            $lines[] = "- {$item->quantity} x {$item->product_name} = Rs " . number_format((float) $item->line_total);
        }

        $lines[] = '';
        $lines[] = 'Total: Rs ' . number_format((float) $order->subtotal) . ' (Cash on Delivery)';

        if ($order->customer_comment) {
            $lines[] = 'Comment: ' . str_replace(["\r", "\n"], ' ', $order->customer_comment);
        }

        $lines[] = '';
        $lines[] = 'Chat with customer: ' . $order->whatsappUrl();

        return implode("\n", $lines);
    }
}
