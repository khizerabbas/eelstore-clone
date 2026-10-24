@extends('layouts.frontend')

@section('title', \App\Support\Content::text('pages.checkout_title') . \App\Support\Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    @if(config('orders.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush

@push('scripts')
    <script>
        window.ueTrack('begin_checkout', {
            value: @js((float) $total),
            items: @js($products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->cart_price, 'quantity' => $p->cart_quantity])->values()),
        });
    </script>
@endpush

@section('content')
    <section class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">{{ \App\Support\Content::text('pages.checkout_title') }}</h1>
            <p class="mt-1 text-sm text-gray-300">
                {{ \App\Support\Content::text('pages.checkout_intro') }}
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @php $itemCount = $products->sum('cart_quantity'); @endphp
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
            {{-- Form --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- Phones: compact order summary above the form (the full one sits in the sidebar on desktop) --}}
                <details class="lg:hidden bg-white border border-gray-200 rounded-lg group">
                    <summary class="flex items-center justify-between gap-3 px-4 py-3 cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        <span class="text-sm text-slate-800">
                            <span class="font-semibold">{{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}</span>
                            <span class="text-gray-500">· show details</span>
                        </span>
                        <span class="inline-flex items-center gap-1 font-bold text-slate-900">
                            {{ rupees($total) }}
                            <x-store-icon name="chevron-down" class="w-4 h-4 text-gray-500 transition-transform group-open:rotate-180"/>
                        </span>
                    </summary>
                    <ul class="divide-y divide-gray-100 text-sm border-t border-gray-100 px-4">
                        @foreach($products as $product)
                            <li class="py-2 flex justify-between gap-3">
                                <span class="text-slate-800 min-w-0">{{ $product->name }} <span class="text-xs text-gray-500">× {{ $product->cart_quantity }}</span></span>
                                <span class="whitespace-nowrap font-medium">{{ rupees($product->cart_subtotal) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="px-4 py-2 border-t border-gray-100 flex justify-between text-xs">
                        <span class="text-gray-500">{{ \App\Support\Content::text('pages.delivery_note') }}</span>
                        <a href="{{ route('cart.index') }}" class="text-accent-ink hover:underline whitespace-nowrap ml-3">Edit cart</a>
                    </div>
                </details>

                <div class="bg-white border border-gray-100 rounded-lg p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-slate-900 mb-1">Your details</h2>
                    <p class="text-xs text-gray-500 mb-5">
                        Only your name and mobile number are required.
                    </p>

                    @if($errors->any() && ! $errors->has('form'))
                        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2" role="alert">
                            Please check the highlighted {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} below.
                        </div>
                    @endif
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
                                    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('name') !border-red-500 bg-red-50/40 @enderror"
                                >
                                @error('name')
                                <p id="name-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
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
                                    @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('phone') !border-red-500 bg-red-50/40 @enderror"
                                >
                                @error('phone')
                                <p id="phone-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
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
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('email') !border-red-500 bg-red-50/40 @enderror"
                            >
                            @error('email')
                            <p id="email-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
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
                                @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('address') !border-red-500 bg-red-50/40 @enderror"
                            >{{ old('address') }}</textarea>
                            @error('address')
                            <p id="address-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
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
                                @error('comment') aria-invalid="true" aria-describedby="comment-error" @enderror
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('comment') !border-red-500 bg-red-50/40 @enderror"
                            >{{ old('comment') }}</textarea>
                            @error('comment')
                            <p id="comment-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if(config('orders.turnstile.site_key'))
                            <div>
                                <div class="cf-turnstile" data-sitekey="{{ config('orders.turnstile.site_key') }}"></div>
                                @error('cf-turnstile-response')
                                <p id="cf-turnstile-response-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-md bg-accent text-on-accent text-sm font-bold hover:bg-accent/80 disabled:opacity-60 disabled:cursor-wait"
                            >
                                {{ \App\Support\Content::text('pages.checkout_submit') }}
                            </button>
                            <p class="mt-2 text-[11px] text-gray-500">
                                {{ \App\Support\Content::text('pages.checkout_consent') }}
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Summary --}}
            <div class="space-y-4">
                <div class="hidden lg:block bg-white border border-gray-100 rounded-lg p-4 lg:sticky lg:top-4">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold">Order Summary</h2>
                        <a href="{{ route('cart.index') }}" class="text-xs text-accent-ink hover:underline">Edit cart</a>
                    </div>

                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach($products as $product)
                            <li class="py-2 flex justify-between gap-3">
                                <span class="text-slate-800">
                                    {{ $product->name }}
                                    <span class="text-xs text-gray-500">× {{ $product->cart_quantity }}</span>
                                </span>
                                <span class="whitespace-nowrap font-medium">{{ rupees($product->cart_subtotal) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-gray-200 mt-3 pt-3 flex justify-between items-center font-semibold text-sm">
                        <span>Total</span>
                        <span>{{ rupees($total) }}</span>
                    </div>
                    <p class="mt-1 text-[11px] text-gray-500">{{ \App\Support\Content::text('pages.delivery_note') }}</p>
                </div>

                <div class="bg-accent/10 border border-accent/40 rounded-lg p-4 text-sm text-slate-800">
                    <h3 class="font-semibold mb-2">{{ \App\Support\Content::text('pages.checkout_steps_title') }}</h3>
                    <ol class="space-y-1.5 list-decimal list-inside text-xs">
                        @foreach(collect(content('pages.checkout_steps', []))->pluck('text')->filter() as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>
@endsection
