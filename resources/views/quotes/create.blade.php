@extends('layouts.frontend')

@php use App\Support\Content; @endphp

@section('title', Content::text('pages.quote_title') . Content::text('seo.title_suffix'))
@section('meta_description', Content::text('pages.quote_intro'))

@push('meta')
    @if(config('orders.turnstile.site_key'))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush

@section('content')
    @php
        $input = 'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent';
        $label = 'block text-xs font-semibold text-gray-700 mb-1';
    @endphp

    <section class="bg-brand text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">{{ Content::text('pages.quote_title') }}</h1>
            <p class="mt-1 text-sm text-gray-300 max-w-3xl">{{ Content::text('pages.quote_intro') }}</p>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white border border-gray-100 rounded-lg p-5 sm:p-6">
            @if($errors->has('form'))
                <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">{{ $errors->first('form') }}</div>
            @endif

            <form action="{{ route('quotes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" data-disable-on-submit>
                @csrf

                {{-- Honeypot field --}}
                <div style="display:none;" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="{{ $label }}">Your name <span class="text-red-500">*</span></label>
                        <input id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" class="{{ $input }}">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="{{ $label }}">Mobile / WhatsApp number <span class="text-red-500">*</span></label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required maxlength="25" inputmode="tel" autocomplete="tel" placeholder="0300 1234567" class="{{ $input }}">
                        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="business_name" class="{{ $label }}">Business / company name <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input id="business_name" name="business_name" value="{{ old('business_name') }}" maxlength="150" autocomplete="organization" class="{{ $input }}">
                    </div>
                    <div>
                        <label for="city" class="{{ $label }}">City / area <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input id="city" name="city" value="{{ old('city') }}" maxlength="100" class="{{ $input }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="email" class="{{ $label }}">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" class="{{ $input }}">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="items" class="{{ $label }}">{{ Content::text('pages.quote_items_label') }}</label>
                    <textarea id="items" name="items" rows="6" maxlength="5000" placeholder="{{ content('pages.quote_items_placeholder') }}" class="{{ $input }}">{{ old('items') }}</textarea>
                    @error('items')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="attachment" class="{{ $label }}">{{ Content::text('pages.quote_attachment_label') }}</label>
                    <input id="attachment" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf"
                           class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-brand file:text-white file:text-sm">
                    <p class="mt-1 text-[11px] text-gray-500">JPG, PNG, WebP or PDF, up to 10 MB.</p>
                    @error('attachment')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                @if($cartItems->isNotEmpty())
                    <div class="border border-gray-200 rounded-md p-3 bg-slate-50">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="include_cart" value="1" @checked(old('include_cart', true)) class="rounded border-gray-300 text-brand focus:ring-accent">
                            {{ Content::text('pages.quote_include_cart') }} ({{ $cartItems->count() }} {{ \Illuminate\Support\Str::plural('item', $cartItems->count()) }})
                        </label>
                        <ul class="mt-2 text-xs text-gray-600 space-y-0.5 pl-6 list-disc">
                            @foreach($cartItems as $item)
                                <li>{{ $item->cart_quantity }} × {{ $item->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(config('orders.turnstile.site_key'))
                    <div>
                        <div class="cf-turnstile" data-sitekey="{{ config('orders.turnstile.site_key') }}"></div>
                        @error('cf-turnstile-response')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div class="pt-2">
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-md bg-accent text-on-accent text-sm font-bold hover:bg-accent/80 disabled:opacity-60 disabled:cursor-wait">
                        {{ Content::text('pages.quote_submit') }}
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
