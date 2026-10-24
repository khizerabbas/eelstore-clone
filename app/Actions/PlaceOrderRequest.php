<?php

namespace App\Actions;

use App\Jobs\SendOwnerWhatsAppAlert;
use App\Mail\NewOrderRequest;
use App\Mail\OrderRequestReceived;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Turns the cart into an order request (Cash on Delivery) and queues the owner notifications.
 * Prices are taken from the products at this moment, never from the client.
 */
class PlaceOrderRequest
{
    /**
     * @param  Collection<int, Product>  $cartItems  from Cart::items()
     */
    public function handle(array $customer, Collection $cartItems, ?string $ip, ?string $userAgent): Order
    {
        $order = DB::transaction(function () use ($customer, $cartItems, $ip, $userAgent) {
            $order = Order::create($customer + [
                'items_count' => $cartItems->sum('cart_quantity'),
                'subtotal'    => $cartItems->sum('cart_subtotal'),
                'ip_address'  => $ip,
                'user_agent'  => $userAgent ? substr($userAgent, 0, 1000) : null,
            ]);

            $order->items()->createMany($cartItems->map(fn (Product $product) => [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'sku'          => $product->sku,
                'unit_price'   => $product->cart_price,
                'quantity'     => $product->cart_quantity,
                'line_total'   => $product->cart_subtotal,
            ])->all());

            return $order;
        });

        $order->load('items');

        // The order is saved at this point – a notification problem must never fail the customer's request
        // (matters when QUEUE_CONNECTION=sync; with a real queue the jobs retry on their own)
        $notify = function (callable $send) {
            try {
                $send();
            } catch (Throwable $e) {
                report($e);
            }
        };

        $adminEmail = config('orders.admin_email') ?: SiteSetting::current()->contact_recipient_email;

        if ($adminEmail) {
            $notify(fn () => Mail::to($adminEmail)->queue(new NewOrderRequest($order)));
        }

        if ($order->customer_email) {
            $notify(fn () => Mail::to($order->customer_email, $order->customer_name)->queue(new OrderRequestReceived($order)));
        }

        $notify(fn () => SendOwnerWhatsAppAlert::dispatch($order));

        return $order;
    }
}
