<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $products = Cart::items();
        $total    = $products->sum('cart_subtotal');

        return view('cart.index', compact('products', 'total'));
    }

    public function add(Request $request, Product $product)
    {
        // Quantity from request (fallback to 1, never < 1)
        $quantity = max(1, (int) $request->input('quantity', 1));

        // If this product is already in the cart, increase quantity
        Cart::put($product, Cart::quantityOf($product) + $quantity);

        $cartCount = Cart::count();

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

        // 0 removes the line
        Cart::put($product, $qty);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(Product $product)
    {
        Cart::remove($product);

        return back()->with('success', 'Product removed from cart.');
    }

    public function clear()
    {
        Cart::clear();

        return back()->with('success', 'Cart cleared.');
    }
}
