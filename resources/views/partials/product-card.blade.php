@php
    use App\Support\Content;

    $wishlistSession  = session('wishlist', []);
    $isInWishlist     = is_array($wishlistSession) && in_array($product->id, $wishlistSession);
    $showWishlistIcon = $showWishlistIcon ?? true;
    $productUrl       = route('product.show', $product->slug);
    $discount         = $product->discountPercent();
@endphp

{{-- Product card: compact on phones (2 per row), always-visible "Add to cart" (works on touch screens) --}}
<div class="group relative bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full transition hover:shadow-md">
    @if($discount)
        <span class="absolute left-2 top-2 z-10 inline-flex items-center gap-1 bg-red-500 text-white text-[10px] sm:text-[11px] font-semibold px-2 py-0.5 rounded-full">
            {{ Content::text('pages.sale_badge') }} −{{ $discount }}%
        </span>
    @endif

    @if($showWishlistIcon)
        <form action="{{ route('wishlist.add', $product) }}" method="POST" data-ajax="wishlist" data-product-id="{{ $product->id }}"
              class="absolute right-2 top-2 z-10">
            @csrf
            <button type="submit" aria-label="{{ $isInWishlist ? 'Remove from wishlist' : 'Add to wishlist' }}"
                    class="w-8 h-8 rounded-full bg-white/90 shadow flex items-center justify-center text-sm hover:text-red-500 transition">
                @if($isInWishlist)
                    <span class="wishlist-heart text-red-500">❤</span>
                @else
                    <span class="wishlist-heart text-gray-400">♡</span>
                @endif
            </button>
        </form>
    @endif

    <a href="{{ $productUrl }}" class="block aspect-square bg-white p-2 sm:p-3" tabindex="-1" aria-hidden="true">
        <x-store-image :src="$product->imageUrl()" :alt="$product->name"
                       class="w-full h-full object-contain transition duration-300 group-hover:scale-105"/>
    </a>

    <div class="flex-1 flex flex-col gap-1 px-3 pb-3 sm:px-4 sm:pb-4">
        {{-- Brand line always takes its space so names line up across cards --}}
        <span class="text-[10px] sm:text-[11px] uppercase tracking-wide text-gray-500 font-semibold truncate min-h-[1.25em]">
            {{ $product->company?->name }}
        </span>

        <h3 class="text-[13px] sm:text-sm font-semibold text-gray-800 leading-snug line-clamp-2 min-h-[2.5em]">
            <a href="{{ $productUrl }}" class="hover:text-accent-ink">{{ $product->name }}</a>
        </h3>

        <div class="flex flex-wrap items-baseline gap-x-2">
            @if($product->isOnSale())
                <span class="text-red-600 font-bold text-sm sm:text-base">{{ rupees($product->sale_price) }}</span>
                <span class="text-gray-400 line-through text-[11px] sm:text-xs">{{ rupees($product->price) }}</span>
            @else
                <span class="text-slate-900 font-bold text-sm sm:text-base">{{ rupees($product->price) }}</span>
            @endif
        </div>

        @if($product->short_description)
            <p class="hidden sm:block text-xs text-gray-500 line-clamp-2">{{ $product->short_description }}</p>
        @endif

        <form action="{{ route('cart.add', $product) }}" method="POST" data-ajax="cart" data-product-id="{{ $product->id }}" class="mt-auto pt-2">
            @csrf
            <input type="hidden" name="quantity" value="1">
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-1.5 rounded-md bg-brand text-white text-xs sm:text-sm font-semibold py-2 hover:bg-brand/90 transition">
                <x-store-icon name="cart" class="w-4 h-4"/>
                <span>{{ Content::text('pages.add_to_cart') }}</span>
            </button>
        </form>
    </div>
</div>
