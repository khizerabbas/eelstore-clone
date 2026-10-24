<?php

namespace App\Models;

use App\Support\PakistaniPhone;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'store_name',
        'contact_phone',
        'contact_email',
        'store_address',
        'facebook_url',
        'instagram_url',
        'whatsapp_url',
        'map_embed_url',
        'contact_recipient_email',
    ];

    // wa.me link to the store: uses whatsapp_url if set, otherwise the contact phone
    public function whatsappLink(?string $text = null): ?string
    {
        $url = $this->whatsapp_url;

        if (! $url) {
            $phone = PakistaniPhone::normalize($this->contact_phone);
            $url   = $phone ? 'https://wa.me/' . ltrim($phone, '+') : null;
        }

        if (! $url || ! $text) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'text=' . rawurlencode($text);
    }

    public static function current(): self
    {
        return static::first() ?? static::create([
            'store_name'              => 'Usman Electronics',
            'contact_phone'           => '+92 323 4146388',
            'contact_email'           => 'info@example.com',
            'store_address'           => "Al-Hamra Electric Store\nMadina Electric Market, 37\nShah Alam Gate, Lahore, Pakistan",
            'facebook_url'            => null,
            'instagram_url'           => null,
            'whatsapp_url'            => null,
            'map_embed_url'           => 'https://maps.app.goo.gl/HW8Fk18AqHee17ht8',
            'contact_recipient_email' => 'test@test.com',
        ]);
    }
}
