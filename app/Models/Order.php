<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\PakistaniPhone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'customer_comment',
        'items_count',
        'subtotal',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = [
        'public_token',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status'                 => OrderStatus::class,
            'subtotal'               => 'decimal:2',
            'contacted_at'           => 'datetime',
            'confirmed_at'           => 'datetime',
            'dispatched_at'          => 'datetime',
            'delivered_at'           => 'datetime',
            'cancelled_at'           => 'datetime',
            'owner_whatsapp_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_token ??= Str::random(48);
            $order->status ??= OrderStatus::New;
        });

        // Reference is derived from the id, so it is unique and short (UE-100042)
        static::created(function (Order $order) {
            $order->reference = 'UE-' . (100000 + $order->id);
            $order->saveQuietly();
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function markStatus(OrderStatus $status, ?string $note = null, ?string $by = null): void
    {
        $this->status = $status;

        // Keep a simple running log in the admin note
        $line = now()->format('d M Y H:i') . ' – ' . $status->getLabel() . ($by ? " by {$by}" : '') . ($note ? ": {$note}" : '');
        $this->admin_note = trim(($this->admin_note ? $this->admin_note . "\n" : '') . $line);

        if ($column = $status->timestampColumn()) {
            $this->{$column} ??= now();
        }

        $this->save();
    }

    // When the order reached a given step (null = not reached yet)
    public function reachedAt(OrderStatus $status)
    {
        $column = $status->timestampColumn();

        return $column ? $this->{$column} : $this->created_at;
    }

    public function displayPhone(): string
    {
        return PakistaniPhone::display($this->customer_phone);
    }

    // 0300 ***4567 – for customer-facing pages reachable by link
    public function maskedPhone(): string
    {
        $display = $this->displayPhone();

        return preg_match('/^(\d{4}) \d{3}(\d{4})$/', $display, $m) ? "{$m[1]} ***{$m[2]}" : $display;
    }

    public function whatsappUrl(?string $text = null): string
    {
        $url = 'https://wa.me/' . ltrim($this->customer_phone, '+');

        return $text ? $url . '?text=' . rawurlencode($text) : $url;
    }
}
