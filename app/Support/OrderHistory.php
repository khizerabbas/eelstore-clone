<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Browser-only "My Orders": an (encrypted, httpOnly) cookie with the customer's order tokens.
 * No accounts – clearing cookies forgets the list, the orders themselves stay in the database.
 */
class OrderHistory
{
    public static function tokens(Request $request): array
    {
        $tokens = json_decode((string) $request->cookie(config('orders.history_cookie')), true);

        if (! is_array($tokens)) {
            return [];
        }

        return array_values(array_filter($tokens, fn ($t) => is_string($t) && preg_match('/^[A-Za-z0-9]{48}$/', $t)));
    }

    public static function remember(Request $request, Order $order): void
    {
        $tokens = self::tokens($request);

        array_unshift($tokens, $order->public_token);

        $tokens = array_slice(array_values(array_unique($tokens)), 0, config('orders.history_max_orders'));

        Cookie::queue(Cookie::make(
            config('orders.history_cookie'),
            json_encode($tokens),
            config('orders.history_cookie_minutes'),
            sameSite: 'lax',
        ));
    }
}
