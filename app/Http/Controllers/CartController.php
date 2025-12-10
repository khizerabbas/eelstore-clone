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
                $line = $cart[$product->id] ?? [];

                $qty = isset($line['quantity']) ? (int) $line['quantity'] : 0;

                // Use stored price if present, otherwise fall back to model price/sale_price
                $price = isset($line['price'])
                    ? (float) $line['price']
                    : (float) ($product->sale_price ?? $product->price);

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
        if (isset($cart[$product->id]) && is_array($cart[$product->id])) {
            $existingQty = isset($cart[$product->id]['quantity'])
                ? (int) $cart[$product->id]['quantity']
                : 0;

            $cart[$product->id]['quantity'] = $existingQty + $quantity;
        } else {
            // New line item
            $cart[$product->id] = [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->sale_price ?? $product->price, // or effective_price if you have it
                'quantity'   => $quantity,
            ];
        }

        // Save back to session
        session(['cart' => $cart]);

        // Recalculate total item count
        $cartCount = 0;
        foreach ($cart as $item) {
            $cartCount += isset($item['quantity']) ? (int) $item['quantity'] : 0;
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

        if ($qty <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = $qty;
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
}
