@extends('layouts.frontend')

@section('title', 'Checkout – ' . config('app.name'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    @if(config('orders.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush

@section('content')
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">Checkout</h1>
            <p class="mt-1 text-sm text-gray-300">
                Send us your order request – we'll call or WhatsApp you to confirm. Pay cash on delivery.
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Form --}}
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-100 rounded-lg p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-900 mb-1">Your details</h2>
                    <p class="text-xs text-gray-500 mb-5">
                        Only your name and mobile number are required.
                    </p>

                    @if($errors->has('form'))
                        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                            {{ $errors->first('form') }}
                        </div>
                    @endif

                    <form action="{{ route('checkout.store') }}" method="POST" class="space-y-4" data-disable-on-submit>
                        @csrf

                        {{-- Honeypot field --}}
                        <div style="display:none;" aria-hidden="true">
                            <label>Website
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Full name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    maxlength="100"
                                    autocomplete="name"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                                >
                                @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Mobile / WhatsApp number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    required
                                    maxlength="25"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    placeholder="0300 1234567"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                                >
                                @error('phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-gray-700 mb-1">
                                Email <span class="text-gray-400 font-normal">(optional – we'll send you a confirmation)</span>
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                maxlength="150"
                                autocomplete="email"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                            >
                            @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="address" class="block text-xs font-semibold text-gray-700 mb-1">
                                Delivery address <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea
                                id="address"
                                name="address"
                                rows="2"
                                maxlength="500"
                                autocomplete="street-address"
                                placeholder="House, street, area, city"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                            >{{ old('address') }}</textarea>
                            @error('address')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="comment" class="block text-xs font-semibold text-gray-700 mb-1">
                                Anything you'd like to tell us? <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea
                                id="comment"
                                name="comment"
                                rows="3"
                                maxlength="1000"
                                placeholder="e.g. best time to call, required wire length, delivery instructions…"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                            >{{ old('comment') }}</textarea>
                            @error('comment')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(config('orders.turnstile.site_key'))
                            <div>
                                <div class="cf-turnstile" data-sitekey="{{ config('orders.turnstile.site_key') }}"></div>
                                @error('cf-turnstile-response')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-md bg-amber-400 text-slate-900 text-sm font-bold hover:bg-amber-300 disabled:opacity-60 disabled:cursor-wait"
                            >
                                Submit Order Request
                            </button>
                            <p class="mt-2 text-[11px] text-gray-500">
                                By submitting you agree that we may contact you on the number above about this order.
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Summary --}}
            <div class="space-y-4">
                <div class="bg-white border border-gray-100 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold">Order Summary</h2>
                        <a href="{{ route('cart.index') }}" class="text-xs text-amber-600 hover:underline">Edit cart</a>
                    </div>

                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach($products as $product)
                            <li class="py-2 flex justify-between gap-3">
                                <span class="text-slate-800">
                                    {{ $product->name }}
                                    <span class="text-xs text-gray-500">× {{ $product->cart_quantity }}</span>
                                </span>
                                <span class="whitespace-nowrap font-medium">Rs {{ number_format($product->cart_subtotal) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-gray-200 mt-3 pt-3 flex justify-between items-center font-semibold text-sm">
                        <span>Total</span>
                        <span>Rs {{ number_format($total) }}</span>
                    </div>
                    <p class="mt-1 text-[11px] text-gray-500">Delivery charges (if any) are confirmed on call.</p>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-slate-800">
                    <h3 class="font-semibold mb-2">How it works</h3>
                    <ol class="space-y-1.5 list-decimal list-inside text-xs">
                        <li>Submit your order request – no payment needed now.</li>
                        <li>We call or WhatsApp you to confirm items and delivery.</li>
                        <li>We deliver to your door – you pay <strong>cash on delivery</strong>.</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@endsection
