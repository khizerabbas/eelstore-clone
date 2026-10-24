<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrderRequest;
use App\Http\Requests\CheckoutRequest;
use App\Support\Cart;
use App\Support\OrderHistory;
use Illuminate\Support\Facades\RateLimiter;

class CheckoutController extends Controller
{
    public function show()
    {
        $products = Cart::items();

        if ($products->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty. Add some products before checking out.');
        }

        return view('checkout.show', [
            'products' => $products,
            'total'    => $products->sum('cart_subtotal'),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrderRequest $placeOrder)
    {
        // Honeypot: bots fill the hidden field – pretend it worked, create nothing
        if ($request->filled('website')) {
            Cart::clear();

            return redirect()->route('home')->with('success', 'Thank you, your order request has been received.');
        }

        $customer = $request->customer();

        // Abuse limits (counted on successful requests only)
        $ipKey    = 'order-request:ip:' . $request->ip();
        $phoneKey = 'order-request:phone:' . $customer['customer_phone'];

        if (RateLimiter::tooManyAttempts($ipKey, config('orders.limits.per_ip_per_hour'))
            || RateLimiter::tooManyAttempts($phoneKey, config('orders.limits.per_phone_per_hour'))) {
            return back()
                ->withErrors(['form' => 'You have sent several order requests recently. Please wait a while, or call/WhatsApp us directly.'])
                ->withInput();
        }

        $products = Cart::items();

        if ($products->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty. Add some products before checking out.');
        }

        $order = $placeOrder->handle($customer, $products, $request->ip(), $request->userAgent());

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($phoneKey, 3600);

        Cart::clear();
        OrderHistory::remember($request, $order);

        return redirect()
            ->route('orders.show', $order)
            ->with('order_placed', true);
    }
}
