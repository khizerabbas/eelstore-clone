<?php

namespace App\Services\WhatsApp;

/**
 * Sends a WhatsApp text to the store owner. Implementations throw on failure so queued jobs can retry.
 * Pick the driver with WHATSAPP_DRIVER (see config/orders.php).
 */
interface WhatsAppNotifier
{
    public function sendToOwner(string $text): void;
}
