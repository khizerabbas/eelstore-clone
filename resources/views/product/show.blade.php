@extends('layouts.frontend')

@section('title', ($product->meta_title ?: $product->name) . \App\Support\Content::text('seo.title_suffix'))
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 155))

@push('meta')
    {!! \App\Support\StructuredData::script(\App\Support\StructuredData::product($product)) !!}
    {!! \App\Support\StructuredData::script(\App\Support\StructuredData::breadcrumbs(array_merge(
        $product->category ? \App\Support\StructuredData::categoryCrumbs($product->category) : [],
        [[$product->name, route('product.show', $product->slug)]]
    ))) !!}
@endpush

@section('content')
    {{-- Breadcrumb + title --}}
    <section class="bg-brand text-white">
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
            {{-- Left: image gallery + full-screen viewer --}}
            <div>
                @php
                    $images = $product->imageUrls('large');
                    $thumbs = $product->imageUrls('thumb');
                @endphp
                @if(count($images))
                    <div x-data="productGallery(@js($images))" x-on:keydown.window="onKey($event)">
                        <div class="relative">
                            <button type="button" x-on:click="openViewer()" aria-label="View larger image"
                                    class="group block w-full bg-white border border-gray-200 rounded-lg h-72 sm:h-96 overflow-hidden cursor-zoom-in"
                                    x-on:touchstart.passive="touchStart($event)" x-on:touchend="touchEnd($event)">
                                <x-store-image :src="$images[0]" :alt="$product->name" :lazy="false" x-bind:src="images[index]"
                                               class="w-full h-full object-contain p-2"/>
                                <span class="absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-full bg-black/60 text-white text-[11px] px-2.5 py-1">
                                    <x-store-icon name="search" class="w-3.5 h-3.5"/> Tap to zoom
                                </span>
                            </button>
                            @if(count($images) > 1)
                                <button type="button" x-on:click="prev()" aria-label="Previous image"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow flex items-center justify-center text-slate-800 hover:bg-white">
                                    <x-store-icon name="arrow-left" class="w-4 h-4"/>
                                </button>
                                <button type="button" x-on:click="next()" aria-label="Next image"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow flex items-center justify-center text-slate-800 hover:bg-white">
                                    <x-store-icon name="arrow-left" class="w-4 h-4 rotate-180"/>
                                </button>
                            @endif
                        </div>

                        @if(count($images) > 1)
                            <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                                @foreach($images as $i => $image)
                                    <button type="button" x-on:click="index = {{ $i }}" aria-label="Show image {{ $i + 1 }}"
                                            class="flex-shrink-0 w-16 h-16 rounded-md border overflow-hidden bg-white"
                                            :class="index === {{ $i }} ? 'border-accent ring-2 ring-accent' : 'border-gray-200'">
                                        <x-store-image :src="$thumbs[$i] ?? $image" :alt="$product->name . ' – image ' . ($i + 1)" class="w-full h-full object-contain"/>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Full-screen viewer --}}
                        <div x-show="open" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="{{ $product->name }} images"
                             class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
                             x-on:click.self="close()" x-on:touchstart.passive="touchStart($event)" x-on:touchend="touchEnd($event)">
                            <img x-bind:src="images[index]" src="{{ $images[0] }}" alt="{{ $product->name }}"
                                 data-fallback="{{ \App\Support\Content::placeholderUrl() }}"
                                 class="max-h-[85vh] max-w-[94vw] object-contain select-none">

                            <button type="button" x-on:click="close()" aria-label="Close"
                                    class="absolute top-3 right-3 w-11 h-11 rounded-full bg-white/15 text-white flex items-center justify-center hover:bg-white/25">
                                <x-store-icon name="close" class="w-6 h-6"/>
                            </button>
                            @if(count($images) > 1)
                                <button type="button" x-on:click="prev()" aria-label="Previous image"
                                        class="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/15 text-white flex items-center justify-center hover:bg-white/25">
                                    <x-store-icon name="arrow-left" class="w-5 h-5"/>
                                </button>
                                <button type="button" x-on:click="next()" aria-label="Next image"
                                        class="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-white/15 text-white flex items-center justify-center hover:bg-white/25">
                                    <x-store-icon name="arrow-left" class="w-5 h-5 rotate-180"/>
                                </button>
                                <span class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-white/15 text-white text-xs px-3 py-1"
                                      x-text="(index + 1) + ' / ' + images.length"></span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-white border border-gray-200 rounded-lg h-72 sm:h-96 flex items-center justify-center overflow-hidden">
                        <x-store-image :alt="$product->name" :lazy="false" class="w-full h-full object-contain"/>
                    </div>
                @endif
            </div>

            {{-- Right: info + actions --}}
            <div class="space-y-4">
                @if($product->company)
                    <div class="text-sm text-gray-600">
                        <span class="font-semibold">Brand:</span>
                        @if($product->company->is_active)
                            <a href="{{ route('brand.show', $product->company->slug) }}" class="text-accent-ink hover:underline">{{ $product->company->name }}</a>
                        @else
                            <span>{{ $product->company->name }}</span>
                        @endif
                    </div>
                @endif

                <div class="space-y-1">
                    @if($product->isOnSale())
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="text-2xl font-bold text-red-600">{{ rupees($product->sale_price) }}</span>
                            <span class="text-sm text-gray-400 line-through">{{ rupees($product->price) }}</span>
                            <span class="inline-flex items-center rounded-full bg-red-500 text-white text-xs font-semibold px-2 py-0.5">
                                Save {{ $product->discountPercent() }}%
                            </span>
                        </div>
                    @else
                        <div class="text-2xl font-bold text-slate-900">
                            {{ rupees($product->price) }}
                        </div>
                    @endif

                    {{-- Orders are confirmed by phone, so "0 in stock" means "on order", not "can't buy" --}}
                    <div class="text-sm">
                        @if($product->stock >= 5)
                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                <x-store-icon name="check" class="w-4 h-4"/> {{ \App\Support\Content::text('pages.stock_in') }}
                            </span>
                        @elseif($product->stock > 0)
                            <span class="inline-flex items-center gap-1 text-amber-700 font-semibold">
                                <x-store-icon name="clock" class="w-4 h-4"/> {{ \App\Support\Content::text('pages.stock_low', ['count' => $product->stock]) }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-sky-700 font-semibold">
                                <x-store-icon name="truck" class="w-4 h-4"/> {{ \App\Support\Content::text('pages.stock_on_order') }}
                            </span>
                        @endif
                    </div>

                    @if($product->sku)
                        <div class="text-xs text-gray-500">
                            SKU: {{ $product->sku }}
                        </div>
                    @endif
                </div>

                {{-- Other sizes (products sharing the same size group – set in the admin) --}}
                @php $otherSizes = $product->otherSizes(); @endphp
                @if($otherSizes->isNotEmpty())
                    <div>
                        <div class="text-xs font-semibold text-gray-700 mb-2">{{ \App\Support\Content::text('pages.product_other_sizes') }}</div>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex flex-col items-center px-3 py-1.5 rounded-md border-2 border-accent bg-accent/10 text-xs font-semibold text-slate-900" aria-current="true">
                                {{ $product->sizeLabel() }}
                                <span class="font-normal text-gray-600">{{ rupees($product->getDisplayPrice()) }}</span>
                            </span>
                            @foreach($otherSizes as $size)
                                <a href="{{ route('product.show', $size->slug) }}"
                                   class="inline-flex flex-col items-center px-3 py-1.5 rounded-md border border-gray-300 bg-white text-xs font-semibold text-slate-800 hover:border-accent">
                                    {{ $size->sizeLabel() }}
                                    <span class="font-normal text-gray-600">{{ rupees($size->getDisplayPrice()) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm text-gray-700 mb-3">
                        {{ $product->short_description ?: \App\Support\Content::text('pages.product_no_description') }}
                    </p>
                    @if($product->description)
                        <div class="prose prose-sm max-w-none text-gray-700">
                            {!! \App\Support\RichText::render($product->description) !!}
                        </div>
                    @endif
                </div>

                    <div class="border-t border-gray-200 pt-4 flex flex-col gap-3 max-w-sm">
                        @if($product->customer_note)
                            <div class="text-sm text-slate-800 bg-accent/10 border border-accent/40 rounded-md px-3 py-2">
                                ℹ️ {{ $product->customer_note }}
                            </div>
                        @endif

                        <form
                            action="{{ route('cart.add', $product) }}"
                            method="POST"
                            data-ajax="cart"
                            data-product-id="{{ $product->id }}"
                            class="flex items-center gap-2"
                        >
                            @csrf

                            <label for="detail-quantity-{{ $product->id }}" class="sr-only">Quantity</label>
                            <div class="inline-flex items-stretch rounded-md border border-gray-300 bg-white overflow-hidden" x-data="{ q: 1 }">
                                <button type="button" x-on:click="q = Math.max(1, q - 1)" aria-label="Decrease quantity"
                                        class="w-10 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                    <x-store-icon name="minus" class="w-4 h-4"/>
                                </button>
                                <input id="detail-quantity-{{ $product->id }}" type="number" name="quantity" x-model.number="q"
                                       value="1" min="1" max="{{ \App\Support\Cart::MAX_LINE_QUANTITY }}" inputmode="numeric"
                                       class="w-14 border-0 border-x border-gray-300 text-center text-sm focus:ring-2 focus:ring-accent [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" x-on:click="q = Math.min({{ \App\Support\Cart::MAX_LINE_QUANTITY }}, q + 1)" aria-label="Increase quantity"
                                        class="w-10 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                    <x-store-icon name="plus" class="w-4 h-4"/>
                                </button>
                            </div>

                            <button
                                type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90"
                            >
                                <x-store-icon name="cart" class="w-4 h-4"/>
                                {{ \App\Support\Content::text('pages.add_to_cart') }}
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
                                class="inline-flex items-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white gap-2"
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

                        @php
                            $askWhatsApp = \App\Models\SiteSetting::current()->whatsappLink(
                                'Assalam o Alaikum, I have a question about: ' . $product->name . ' – ' . route('product.show', $product->slug)
                            );
                        @endphp
                        @if($askWhatsApp)
                            <a href="{{ $askWhatsApp }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                <x-store-icon name="whatsapp" class="w-4 h-4"/>
                                {{ \App\Support\Content::text('pages.product_whatsapp_button') }}
                            </a>
                        @endif
                    </div>

            </div>
        </div>
    </section>

    {{-- Related products --}}
    @if($related->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
            <h2 class="text-lg font-semibold mb-4">Related Products</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-6">
                @foreach($related as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif
@endsection
