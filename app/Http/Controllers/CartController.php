<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $cart = session('cart', []);

        // If cart is empty or not an array, show empty cart
        if (!is_array($cart) || empty($cart)) {
            return view('cart.index', [
                'products' => collect(),
                'total'    => 0,
            ]);
        }

        $productIds = array_keys($cart);

        $products = Product::whereIn('id', $productIds)
            ->get()
            ->map(function (Product $product) use ($cart) {
                $qty = $this->lineQuantity($cart[$product->id] ?? null);

                // Always price from the product so cart matches the shop/product pages
                $price = $product->getDisplayPrice();

                $product->cart_quantity = $qty;
                $product->cart_price    = $price;
                $product->cart_subtotal = $qty * $price;

                return $product;
            });

        $total = $products->sum('cart_subtotal');

        return view('cart.index', compact('products', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        // Quantity from request (fallback to 1, never < 1)
        $quantity = max(1, (int) $request->input('quantity', 1));

        // Normalize cart from session
        $cart = session('cart', []);

        if (!is_array($cart)) {
            // If something weird was stored in session before, reset it
            $cart = [];
        }

        // If this product is already in the cart, increase quantity
        $existingQty = $this->lineQuantity($cart[$product->id] ?? null);

        $cart[$product->id] = $this->makeLine($product, $existingQty + $quantity);

        // Save back to session
        session(['cart' => $cart]);

        // Recalculate total item count
        $cartCount = 0;
        foreach ($cart as $item) {
            $cartCount += $this->lineQuantity($item);
        }

        // If this is an AJAX/JSON request (our fetch call), return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Product added to cart',
                'cart_count' => $cartCount,
            ]);
        }

        // Fallback for normal form post
        return redirect()->back()->with('success', 'Product added to cart');
    }

    public function update(Request $request, Product $product)
    {
        $qty = max(0, (int) $request->input('quantity', 1));

        $cart = session('cart', []);

        if (!is_array($cart)) {
            $cart = [];
        }

        if ($qty <= 0) {
            unset($cart[$product->id]);
        } else {
            // Keep the same line shape as add(), otherwise index() reads quantity as 0
            $cart[$product->id] = $this->makeLine($product, $qty);
        }

        session(['cart' => $cart]);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Product $product)
    {
        $cart = session('cart', []);
        unset($cart[$product->id]);
        session(['cart' => $cart]);

        return back()->with('success', 'Product removed from cart.');
    }

    public function clear()
    {
        session()->forget('cart');

        return back()->with('success', 'Cart cleared.');
    }

    // Session cart line: price is not stored, it is always read live from the product
    private function makeLine(Product $product, int $quantity): array
    {
        return [
            'product_id' => $product->id,
            'name'       => $product->name,
            'quantity'   => $quantity,
        ];
    }

    // Tolerates old sessions where update() stored a bare integer
    private function lineQuantity($line): int
    {
        if (is_array($line)) {
            return isset($line['quantity']) ? (int) $line['quantity'] : 0;
        }

        return is_numeric($line) ? (int) $line : 0;
    }
}
