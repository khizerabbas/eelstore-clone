@php
    $wishlistSession = session('wishlist', []);
    $isInWishlist = is_array($wishlistSession) && in_array($product->id, $wishlistSession);

    // control whether the heart icon should show (default: true)
    $showWishlistIcon = $showWishlistIcon ?? true;
@endphp

<div
    class="group relative bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md hover:-translate-y-1">

    {{-- Top bar: SALE + wishlist --}}
    <div class="absolute inset-x-0 top-0 z-10 flex items-start justify-between px-2 pt-2 pointer-events-none">
        <div>
            @if($product->is_on_sale)
                <span
                    class="inline-flex items-center bg-red-500 text-white text-[10px] font-semibold px-2 py-1 rounded-full pointer-events-auto">
                SALE
            </span>
            @endif
        </div>

        @if($showWishlistIcon)
            <div class="pointer-events-auto">
                <form action="{{ route('wishlist.add', $product) }}" method="POST" data-ajax="wishlist"
                      data-product-id="{{ $product->id }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-8 h-8 rounded-full bg-white shadow flex items-center justify-center text-xs hover:text-red-500 transition"
                        aria-label="Add to wishlist"
                    >
                        @if($isInWishlist)
                            <span class="wishlist-heart text-red-500">❤</span>
                        @else
                            <span class="wishlist-heart text-gray-400">♡</span>
                        @endif
                    </button>
                </form>
            </div>
        @endif
    </div>


    {{-- Product image / placeholder --}}
    <a href="{{ route('product.show', $product->slug) }}"
       class="block w-full bg-slate-50 h-40 sm:h-44 flex items-center justify-center">
        {{-- TODO: replace with real image when available --}}
        <span class="text-3xl">⚡</span>
    </a>

    {{-- Text content --}}
    <div class="p-4 space-y-2 pb-4 md:pb-10">
        @if($product->company)
            <span class="text-xs text-gray-500 uppercase min-h-[1rem] block">
    {{ $product->company->name ?? '' }}
</span>
        @endif

        <h3 class="text-sm font-semibold text-gray-800 leading-snug line-clamp-2">
            <a href="{{ route('product.show', $product->slug) }}" class="hover:text-slate-900">
                <p class="font-semibold text-lg min-h-[3rem]">
                    {{ $product->name }}
                </p>
            </a>
        </h3>

        {{-- Price --}}
        <div class="flex items-baseline gap-2">
            @if($product->is_on_sale && $product->sale_price)
                <span class="text-red-600 font-bold text-sm">
                    Rs {{ number_format($product->sale_price, 0) }}
                </span>
                <span class="text-gray-400 line-through text-xs">
                    Rs {{ number_format($product->price, 0) }}
                </span>
            @else
                <span class="text-slate-900 font-bold text-sm">
                    Rs {{ number_format($product->price, 0) }}
                </span>
            @endif
        </div>

        @if($product->short_description)
                <p class="text-gray-600 text-sm min-h-[3rem]">
                    {{ $product->short_description }}
                </p>
        @endif
    </div>

    {{-- Desktop: hover action bar --}}
    <div class="hidden md:flex absolute inset-x-0 bottom-0 bg-white border-t border-gray-200 px-3 py-2
                items-center justify-between opacity-0 translate-y-3
                group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-150">

        {{-- Add to Cart --}}
        <form action="{{ route('cart.add', $product) }}"
              method="POST"
              data-ajax="cart"
              data-product-id="{{ $product->id }}">

            @csrf
            <input type="hidden" name="quantity" value="1">
            <button
                type="submit"
                class="flex items-center gap-1 text-[11px] font-medium text-gray-700 hover:text-slate-900"
            >
                🛒 <span>Add to Cart</span>
            </button>
        </form>

        {{-- View Details --}}
        <a
            href="{{ route('product.show', $product->slug) }}"
            class="flex items-center gap-1 text-[11px] font-medium text-gray-700 hover:text-slate-900"
        >
            👁️ <span>Details</span>
        </a>
    </div>

    {{-- Mobile: full-width buttons --}}
    <div class="md:hidden px-4 pb-4 space-y-2">
        <a
            href="{{ route('product.show', $product->slug) }}"
            class="block w-full text-center text-xs font-semibold rounded-md bg-slate-900 text-white py-2 hover:bg-slate-800"
        >
            View Details
        </a>

        <form action="{{ route('cart.add', $product) }}"
              method="POST"
              data-ajax="cart"
              data-product-id="{{ $product->id }}">

            @csrf
            <input type="hidden" name="quantity" value="1">
            <button
                type="submit"
                class="w-full text-center text-xs font-semibold rounded-md border border-slate-900 text-slate-900 py-2 hover:bg-slate-900 hover:text-white"
            >
                Add to Cart
            </button>
        </form>
    </div>
</div>
