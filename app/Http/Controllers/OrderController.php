<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use App\Support\OrderHistory;
use Illuminate\Http\Request;

// Customer-facing order pages. Access is by the secret token (no login); pages are never indexed.
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $tokens = OrderHistory::tokens($request);

        $orders = empty($tokens)
            ? collect()
            : Order::withCount('items')
                ->whereIn('public_token', $tokens)
                ->latest()
                ->get();

        return response()
            ->view('orders.index', compact('orders'))
            ->withHeaders($this->privateHeaders());
    }

    public function show(Request $request, Order $order)
    {
        $order->load('items');

        // Opening the private link on another device adds it to that browser's list too
        OrderHistory::remember($request, $order);

        return response()
            ->view('orders.show', compact('order'))
            ->withHeaders($this->privateHeaders());
    }

    public function reorder(Order $order)
    {
        $added = 0;

        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::where('is_active', true)->find($item->product_id) : null;

            if ($product) {
                Cart::put($product, Cart::quantityOf($product) + $item->quantity);
                $added++;
            }
        }

        return redirect()->route('cart.index')->with(
            $added ? 'success' : 'error',
            $added
                ? 'Items from order ' . $order->reference . ' were added to your cart. Prices are updated to today\'s prices.'
                : 'Sorry, the products from this order are no longer available.'
        );
    }

    private function privateHeaders(): array
    {
        return [
            'X-Robots-Tag'    => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer', // don't leak the token to other sites
            'Cache-Control'   => 'private, no-store',
        ];
    }
}
