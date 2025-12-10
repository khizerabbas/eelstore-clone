@extends('layouts.frontend')

@section('title', $product->name . ' – ' . config('app.name'))

@section('content')
    {{-- Breadcrumb + title --}}
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <nav class="text-xs text-gray-400 mb-4">
                <a href="{{ route('home') }}">Home</a>
                <span class="mx-1">/</span>
                <a href="{{ route('shop.index') }}">Shop</a>
                <span class="mx-1">/</span>
                <span class="text-gray-100">{{ $product->name }}</span>
            </nav>


            <h1 class="text-2xl font-bold">{{ $product->name }}</h1>
        </div>
    </section>

    {{-- Product main content --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Left: image(s) --}}
            <div>
                <div class="bg-slate-50 border border-gray-200 rounded-lg h-72 flex items-center justify-center mb-3">
                    {{-- Placeholder image / icon --}}
                    <span class="text-6xl">⚡</span>
                </div>
                <p class="text-xs text-gray-500">
                    (We’ll replace this with actual product images later; for now it’s a placeholder.)
                </p>
            </div>

            {{-- Right: info + actions --}}
            <div class="space-y-4">
                @if($product->company)
                    <div class="text-sm text-gray-600">
                        <span class="font-semibold">Company:</span>
                        <span>{{ $product->company->name }}</span>
                    </div>
                @endif

                <div class="space-y-1">
                    @if($product->is_on_sale && $product->sale_price)
                        <div class="flex items-baseline gap-3">
                            <span class="text-2xl font-bold text-red-600">
                                Rs {{ number_format($product->sale_price, 2) }}
                            </span>
                            <span class="text-sm text-gray-400 line-through">
                                Rs {{ number_format($product->price, 2) }}
                            </span>
                        </div>
                    @else
                        <div class="text-2xl font-bold text-slate-900">
                            Rs {{ number_format($product->price, 2) }}
                        </div>
                    @endif

                    <div class="text-xs text-gray-500">
                        @if($product->stock > 0)
                            <span class="text-emerald-600 font-semibold">In Stock</span>
                            <span> ({{ $product->stock }} available)</span>
                        @else
                            <span class="text-red-600 font-semibold">Out of Stock</span>
                        @endif
                    </div>

                    @if($product->sku)
                        <div class="text-xs text-gray-500">
                            SKU: {{ $product->sku }}
                        </div>
                    @endif
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm text-gray-700 mb-3">
                        {{ $product->short_description ?? 'High quality product from Usman Electronics.' }}
                    </p>
                    @if($product->description)
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    @endif
                </div>

                    <div class="border-t border-gray-200 pt-4 flex flex-col gap-3 max-w-sm">
                        <form
                            action="{{ route('cart.add', $product) }}"
                            method="POST"
                            data-ajax="cart"
                            data-product-id="{{ $product->id }}"
                            class="flex items-center gap-2"
                        >
                            @csrf

                            <label for="detail-quantity-{{ $product->id }}" class="text-sm text-gray-700">
                                Quantity:
                            </label>
                            <input
                                id="detail-quantity-{{ $product->id }}"
                                type="number"
                                name="quantity"
                                value="1"
                                min="1"
                                class="w-16 border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                            >

                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800"
                            >
                                Add to Cart
                            </button>
                        </form>


                        @php
                            $wishlistSession = session('wishlist', []);
                            $isInWishlist = is_array($wishlistSession) && in_array($product->id, $wishlistSession);
                        @endphp

                        <form
                            action="{{ route('wishlist.add', $product) }}"
                            method="POST"
                            data-ajax="wishlist"
                            data-product-id="{{ $product->id }}"
                            class="mt-3"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 rounded-md border border-slate-900 text-slate-900 text-sm font-semibold hover:bg-slate-900 hover:text-white gap-2"
                            >
                                @if($isInWishlist)
                                    <span class="wishlist-heart text-red-500">❤</span>
                                    <span class="wishlist-label">Added to Wishlist</span>
                                @else
                                    <span class="wishlist-heart text-gray-400">♡</span>
                                    <span class="wishlist-label">Add to Wishlist</span>
                                @endif
                            </button>
                        </form>


                    </div>

            </div>
        </div>
    </section>

    {{-- Related products --}}
    @if($related->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <h2 class="text-lg font-semibold mb-4">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($related as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
@endsection
