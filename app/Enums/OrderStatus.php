<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New            = 'new';
    case Contacted      = 'contacted';
    case Confirmed      = 'confirmed';
    case OutForDelivery = 'out_for_delivery';
    case Delivered      = 'delivered';
    case Cancelled      = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New            => 'Request received',
            self::Contacted      => 'We contacted you',
            self::Confirmed      => 'Order confirmed',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered      => 'Delivered',
            self::Cancelled      => 'Cancelled',
        };
    }

    // What the customer should do / expect at this stage (shown on the status page)
    public function customerMessage(): string
    {
        return match ($this) {
            self::New            => 'We have received your order request. Our team will call or WhatsApp you shortly to confirm it.',
            self::Contacted      => 'We have reached out to you. Please check your WhatsApp, phone calls and email to confirm your order.',
            self::Confirmed      => 'Your order is confirmed and is being prepared. You will pay cash on delivery.',
            self::OutForDelivery => 'Your order is on its way. Please keep cash ready for payment on delivery.',
            self::Delivered      => 'Your order has been delivered. Thank you for shopping with us!',
            self::Cancelled      => 'This order request was cancelled. Contact us if you think this is a mistake.',
        };
    }

    // Tailwind classes for the status badge
    public function badgeClasses(): string
    {
        return match ($this) {
            self::New            => 'bg-amber-100 text-amber-800',
            self::Contacted      => 'bg-sky-100 text-sky-800',
            self::Confirmed      => 'bg-indigo-100 text-indigo-800',
            self::OutForDelivery => 'bg-violet-100 text-violet-800',
            self::Delivered      => 'bg-emerald-100 text-emerald-800',
            self::Cancelled      => 'bg-red-100 text-red-700',
        };
    }

    // Column that records when the order entered this status
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::New            => null,
            self::Contacted      => 'contacted_at',
            self::Confirmed      => 'confirmed_at',
            self::OutForDelivery => 'dispatched_at',
            self::Delivered      => 'delivered_at',
            self::Cancelled      => 'cancelled_at',
        };
    }

    // Happy-path steps shown as the timeline (cancelled is shown separately)
    public static function timeline(): array
    {
        return [self::New, self::Contacted, self::Confirmed, self::OutForDelivery, self::Delivered];
    }
}
