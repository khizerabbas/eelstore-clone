<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlist = session('wishlist', []);

        if (empty($wishlist)) {
            $products = collect();
        } else {
            $products = Product::with(['category', 'company'])
                ->whereIn('id', $wishlist)
                ->where('is_active', true)
                ->get();
        }

        return view('wishlist.index', compact('products'));
    }

    public function add(Request $request, Product $product)
    {
        $wishlist = session('wishlist', []);

        if (in_array($product->id, $wishlist)) {
            // toggle off
            $wishlist = array_values(array_diff($wishlist, [$product->id]));
            $inWishlist = false;
            $msg = 'Removed from wishlist';
        } else {
            $wishlist[] = $product->id;
            $inWishlist = true;
            $msg = 'Added to wishlist';
        }

        session(['wishlist' => $wishlist]);

        $wishlistCount = count($wishlist);

        if ($request->expectsJson()) {
            return response()->json([
                'success'         => true,
                'message'         => $msg,
                'wishlist_count'  => $wishlistCount,
                'in_wishlist'     => $inWishlist,
                'product_id'      => $product->id,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function remove(Product $product)
    {
        $wishlist = session('wishlist', []);
        $wishlist = array_values(array_filter($wishlist, fn ($id) => (int)$id !== (int)$product->id));

        session(['wishlist' => $wishlist]);

        return back()->with('success', 'Product removed from wishlist.');
    }

    public function clear()
    {
        session()->forget('wishlist');

        return back()->with('success', 'Wishlist cleared.');
    }
}
