<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\LogsAdminActivity;
use App\Support\PakistaniPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

// Bulk / trade quote request from /bulk-quote (electricians, contractors, shops)
class QuoteRequest extends Model
{
    use LogsAdminActivity;

    public const ATTACHMENT_DISK = 'local'; // private: storage/app/private, never publicly reachable

    protected $fillable = [
        'name',
        'phone',
        'email',
        'business_name',
        'city',
        'items',
        'cart_items',
        'attachment_path',
        'attachment_name',
        'status',
        'admin_note',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = ['ip_address', 'user_agent'];

    protected function casts(): array
    {
        return [
            'status'                 => QuoteStatus::class,
            'cart_items'             => 'array',
            'owner_whatsapp_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (QuoteRequest $quote) => $quote->status ??= QuoteStatus::New);

        // Short reference derived from the id: QR-100001
        static::created(function (QuoteRequest $quote) {
            $quote->reference = 'QR-' . (100000 + $quote->id);
            $quote->saveQuietly();
        });

        // The uploaded list goes with the request
        static::deleted(function (QuoteRequest $quote) {
            if ($quote->attachment_path) {
                Storage::disk(self::ATTACHMENT_DISK)->delete($quote->attachment_path);
            }
        });
    }

    public function activityLabel(): string
    {
        return 'Quote request: ' . ($this->reference ?? '#' . $this->id);
    }

    public function displayPhone(): string
    {
        return PakistaniPhone::display($this->phone);
    }

    public function whatsappUrl(?string $text = null): string
    {
        $url = 'https://wa.me/' . ltrim($this->phone, '+');

        return $text ? $url . '?text=' . rawurlencode($text) : $url;
    }

    public function cartTotal(): float
    {
        return (float) collect($this->cart_items ?? [])->sum('line_total');
    }
}
