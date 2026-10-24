<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Session cart. Lines are stored as [product_id => ['product_id', 'name', 'quantity']].
 * Prices are never stored – they are always read live from the product.
 */
class Cart
{
    public const MAX_LINE_QUANTITY = 1000;

    public static function raw(): array
    {
        $cart = session('cart', []);

        return is_array($cart) ? $cart : [];
    }

    // Tolerates old sessions where a line was stored as a bare integer
    public static function lineQuantity($line): int
    {
        if (is_array($line)) {
            return isset($line['quantity']) ? (int) $line['quantity'] : 0;
        }

        return is_numeric($line) ? (int) $line : 0;
    }

    public static function quantityOf(Product $product): int
    {
        return self::lineQuantity(self::raw()[$product->id] ?? null);
    }

    public static function put(Product $product, int $quantity): void
    {
        $cart = self::raw();
        $quantity = min($quantity, self::MAX_LINE_QUANTITY);

        if ($quantity <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'quantity'   => $quantity,
            ];
        }

        session(['cart' => $cart]);
    }

    public static function remove(Product $product): void
    {
        self::put($product, 0);
    }

    public static function clear(): void
    {
        session()->forget('cart');
    }

    public static function count(): int
    {
        $count = 0;

        foreach (self::raw() as $line) {
            $count += self::lineQuantity($line);
        }

        return $count;
    }

    /**
     * Active products in the cart, each with cart_quantity, cart_price and cart_subtotal set.
     */
    public static function items(): Collection
    {
        $cart = self::raw();

        if (empty($cart)) {
            return collect();
        }

        return Product::with('company')
            ->whereIn('id', array_keys($cart))
            ->where('is_active', true)
            ->get()
            ->map(function (Product $product) use ($cart) {
                $qty   = self::lineQuantity($cart[$product->id] ?? null);
                $price = $product->getDisplayPrice();

                $product->cart_quantity = $qty;
                $product->cart_price    = $price;
                $product->cart_subtotal = $qty * $price;

                return $product;
            })
            ->filter(fn (Product $product) => $product->cart_quantity > 0)
            ->values();
    }
}
