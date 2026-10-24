@extends('layouts.frontend')

@section('title', \App\Support\Content::text('pages.cart_title') . \App\Support\Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <section class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">{{ \App\Support\Content::text('pages.cart_title') }}</h1>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @if(session('success'))
            <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-3 py-2">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                {{ session('error') }}
            </div>
        @endif

        @if($products->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
                <x-store-icon name="cart" class="w-12 h-12 mx-auto text-gray-300"/>
                <p class="mt-3 text-sm text-gray-700">{{ \App\Support\Content::text('pages.cart_empty') }}</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('shop.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">
                        Continue Shopping
                    </a>
                    <a href="{{ route('quotes.create') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white">
                        Request a quote
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <div class="lg:col-span-2 space-y-3">
                    @foreach($products as $product)
                        <div class="bg-white border border-gray-200 rounded-lg p-3 sm:p-4 flex gap-3 sm:gap-4">
                            <a href="{{ route('product.show', $product->slug) }}"
                               class="w-20 h-20 sm:w-24 sm:h-24 bg-slate-50 rounded-md flex items-center justify-center overflow-hidden flex-shrink-0">
                                <x-store-image :src="$product->imageUrl('thumb')" :alt="$product->name" class="w-full h-full object-contain"/>
                            </a>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <a href="{{ route('product.show', $product->slug) }}"
                                           class="text-sm font-semibold text-slate-900 hover:text-accent-ink line-clamp-2">
                                            {{ $product->name }}
                                        </a>
                                        <div class="text-xs text-gray-500">
                                            {{ collect([$product->company?->name, $product->sku ? 'SKU ' . $product->sku : null])->filter()->join(' · ') }}
                                        </div>
                                    </div>
                                    <form action="{{ route('cart.remove', $product) }}" method="POST" class="flex-shrink-0">
                                        @csrf
                                        <button type="submit" aria-label="Remove {{ $product->name }}" title="Remove"
                                                class="w-8 h-8 -mr-1 -mt-1 rounded-md flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50">
                                            <x-store-icon name="trash" class="w-4 h-4"/>
                                        </button>
                                    </form>
                                </div>

                                <div class="mt-3 flex flex-wrap items-end justify-between gap-2">
                                    {{-- Quantity: − / + apply straight away; a typed number applies on change. Three small forms so each
                                         button sends exactly one quantity (works without JavaScript too). --}}
                                    <div class="inline-flex items-stretch rounded-md border border-gray-300 bg-white overflow-hidden">
                                        <form action="{{ route('cart.update', $product) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="quantity" value="{{ $product->cart_quantity - 1 }}">
                                            <button type="submit" aria-label="Decrease quantity of {{ $product->name }}"
                                                    class="w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                                <x-store-icon :name="$product->cart_quantity > 1 ? 'minus' : 'trash'" class="w-4 h-4"/>
                                            </button>
                                        </form>
                                        <form action="{{ route('cart.update', $product) }}" method="POST">
                                            @csrf
                                            <label for="qty-{{ $product->id }}" class="sr-only">Quantity of {{ $product->name }}</label>
                                            <input id="qty-{{ $product->id }}" type="number" name="quantity" min="0"
                                                   max="{{ \App\Support\Cart::MAX_LINE_QUANTITY }}" inputmode="numeric"
                                                   value="{{ $product->cart_quantity }}" data-submit-on-change
                                                   class="w-12 h-9 border-0 border-x border-gray-300 text-center text-sm px-0 focus:ring-2 focus:ring-accent [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                        </form>
                                        <form action="{{ route('cart.update', $product) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="quantity" value="{{ min($product->cart_quantity + 1, \App\Support\Cart::MAX_LINE_QUANTITY) }}">
                                            <button type="submit" aria-label="Increase quantity of {{ $product->name }}"
                                                    class="w-9 h-9 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                                <x-store-icon name="plus" class="w-4 h-4"/>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="text-right">
                                        <div class="text-sm font-bold text-slate-900">{{ rupees($product->cart_subtotal) }}</div>
                                        @if($product->cart_quantity > 1)
                                            <div class="text-[11px] text-gray-500">{{ rupees($product->getDisplayPrice()) }} each</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between pt-1">
                        <a href="{{ route('shop.index') }}" class="inline-flex items-center gap-1 text-sm text-accent-ink hover:underline">
                            <x-store-icon name="arrow-left" class="w-4 h-4"/> Continue shopping
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST" data-confirm="Remove all items from your cart?">
                            @csrf
                            <button type="submit" class="text-xs text-gray-500 hover:text-red-600 underline">Clear cart</button>
                        </form>
                    </div>
                </div>

                {{-- Summary --}}
                @php $itemCount = $products->sum('cart_quantity'); @endphp
                <div class="bg-white border border-gray-200 rounded-lg p-4 h-fit lg:sticky lg:top-4">
                    <h2 class="text-sm font-semibold mb-3">Order Summary</h2>

                    <div class="flex justify-between items-center mb-2 text-sm">
                        <span>Subtotal ({{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }})</span>
                        <span>{{ rupees($total) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2 text-xs text-gray-500">
                        <span>Delivery</span>
                        <span>Confirmed on call</span>
                    </div>

                    <div class="border-t border-gray-200 mt-3 pt-3 flex justify-between items-center font-semibold">
                        <span>Total</span>
                        <span class="text-lg">{{ rupees($total) }}</span>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <a href="{{ route('checkout.show') }}"
                           class="inline-flex items-center justify-center px-4 py-3 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">
                            {{ \App\Support\Content::text('pages.cart_checkout_button') }}
                        </a>
                        <p class="text-[11px] text-gray-500 text-center">
                            {{ \App\Support\Content::text('pages.cart_cod_note') }}
                        </p>
                        <a href="{{ route('quotes.create') }}" class="text-xs text-center text-accent-ink hover:underline">
                            {{ \App\Support\Content::text('pages.cart_quote_link') }}
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
