@extends('layouts.frontend')

@section('title', 'Cart – ' . config('app.name'))

@section('content')
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">Shopping Cart</h1>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
            <p class="text-sm text-gray-600">
                Your cart is currently empty.
            </p>
            <div class="mt-4">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                    Continue Shopping
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-4">
                    @foreach($products as $product)
                        <div class="bg-white border border-gray-100 rounded-lg p-4 flex gap-4 items-start">
                            <div class="w-20 h-20 bg-slate-50 rounded-md flex items-center justify-center text-3xl">
                                ⚡
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <a href="{{ route('product.show', $product->slug) }}"
                                           class="text-sm font-semibold text-slate-900 hover:text-amber-600">
                                            {{ $product->name }}
                                        </a>
                                        @if($product->company)
                                            <div class="text-xs text-gray-500">
                                                {{ $product->company->name }}
                                            </div>
                                        @endif
                                        @if($product->sku)
                                            <div class="text-[11px] text-gray-400">
                                                SKU: {{ $product->sku }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-right text-sm">
                                        <div class="font-semibold">
                                            Rs {{ number_format($product->getDisplayPrice(), 2) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            per unit
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-3">
                                    <form action="{{ route('cart.update', $product) }}" method="POST" class="flex items-center gap-2">
                                        @csrf
                                        <label class="text-xs text-gray-600">Qty:</label>
                                        <input
                                            type="number"
                                            name="quantity"
                                            min="0"
                                            value="{{ $product->cart_quantity }}"
                                            class="w-20 border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400"
                                        >
                                        <button
                                            type="submit"
                                            class="text-xs px-3 py-1 rounded-md border border-slate-900 text-slate-900 hover:bg-slate-900 hover:text-white"
                                        >
                                            Update
                                        </button>
                                    </form>

                                    <form action="{{ route('cart.remove', $product) }}" method="POST">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="text-xs px-3 py-1 rounded-md border border-red-600 text-red-600 hover:bg-red-600 hover:text-white"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="text-right text-sm font-semibold">
                                Rs {{ number_format($product->cart_subtotal, 2) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Summary --}}
                <div class="bg-white border border-gray-100 rounded-lg p-4 h-fit">
                    <h2 class="text-sm font-semibold mb-3">Order Summary</h2>

                    <div class="flex justify-between items-center mb-2 text-sm">
                        <span>Subtotal</span>
                        <span>Rs {{ number_format($total, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-2 text-xs text-gray-500">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>

                    <div class="border-t border-gray-200 mt-3 pt-3 flex justify-between items-center font-semibold text-sm">
                        <span>Total</span>
                        <span>Rs {{ number_format($total, 2) }}</span>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800"
                        >
                            Proceed to Checkout (placeholder)
                        </button>

                        <form action="{{ route('cart.clear') }}" method="POST">
                            @csrf
                            <button
                                type="submit"
                                class="w-full inline-flex items-center justify-center px-4 py-2 rounded-md border border-gray-300 text-xs text-gray-600 hover:bg-gray-100"
                            >
                                Clear Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endsection
