<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Official WhatsApp Cloud API (Meta). Business-initiated messages must use an approved template;
 * the template needs one body parameter ({{1}}) that receives the alert text.
 */
class CloudApiWhatsAppNotifier implements WhatsAppNotifier
{
    public function __construct(private readonly array $config)
    {
    }

    public function sendToOwner(string $text): void
    {
        foreach (['token', 'phone_number_id', 'to', 'template'] as $key) {
            if (empty($this->config[$key])) {
                throw new RuntimeException("WhatsApp Cloud API is not configured ({$key} missing).");
            }
        }

        $url = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $this->config['api_version'] ?? 'v21.0',
            $this->config['phone_number_id']
        );

        // Template parameters can't contain newlines/tabs or 4+ consecutive spaces
        $param = preg_replace('/\s{2,}|\n|\t/', ' | ', $text);

        $response = Http::timeout(20)
            ->withToken($this->config['token'])
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to'                => ltrim($this->config['to'], '+'),
                'type'              => 'template',
                'template'          => [
                    'name'       => $this->config['template'],
                    'language'   => ['code' => $this->config['language'] ?? 'en'],
                    'components' => [[
                        'type'       => 'body',
                        'parameters' => [['type' => 'text', 'text' => mb_substr($param, 0, 1000)]],
                    ]],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp Cloud API request failed: HTTP ' . $response->status() . ' ' . substr($response->body(), 0, 300));
        }
    }
}
