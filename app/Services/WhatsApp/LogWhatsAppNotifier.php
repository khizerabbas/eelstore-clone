<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

// Local/dev driver: writes the alert to the log instead of sending it
class LogWhatsAppNotifier implements WhatsAppNotifier
{
    public function sendToOwner(string $text): void
    {
        Log::info("[WhatsApp → owner]\n" . $text);
    }
}
