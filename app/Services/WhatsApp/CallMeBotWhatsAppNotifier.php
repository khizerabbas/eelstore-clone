<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * CallMeBot (https://www.callmebot.com) – free WhatsApp messages to your own number.
 * Setup (owner's phone): follow https://www.callmebot.com/blog/free-api-whatsapp-messages/ –
 * add the bot number listed there, send it "I allow callmebot to send me messages",
 * then put the API key it replies with in CALLMEBOT_API_KEY and your number in CALLMEBOT_PHONE.
 */
class CallMeBotWhatsAppNotifier implements WhatsAppNotifier
{
    public function __construct(
        private readonly ?string $phone,
        private readonly ?string $apiKey,
    ) {
    }

    public function sendToOwner(string $text): void
    {
        if (! $this->phone || ! $this->apiKey) {
            throw new RuntimeException('CallMeBot is not configured (CALLMEBOT_PHONE / CALLMEBOT_API_KEY).');
        }

        $response = Http::timeout(20)
            ->retry(2, 1000, throw: false)
            ->get('https://api.callmebot.com/whatsapp.php', [
                'phone'  => $this->phone,
                'text'   => $text,
                'apikey' => $this->apiKey,
            ]);

        // CallMeBot can answer 200 with an error page, so check the body too
        if ($response->failed() || stripos($response->body(), 'error') !== false) {
            throw new RuntimeException('CallMeBot request failed: HTTP ' . $response->status() . ' ' . substr(strip_tags($response->body()), 0, 300));
        }
    }
}
