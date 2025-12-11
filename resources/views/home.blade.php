@extends('layouts.frontend')

@section('title', 'Home – ' . config('app.name'))

@section('content')
    {{-- Hero section --}}
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                {{-- Left: text content --}}
                <div class="space-y-6">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                        {{ $settings->home_hero_title ?? 'Premium Electrical Supplies & Cables' }}
                    </h1>

                    <p class="text-sm sm:text-base text-slate-300 max-w-xl">
                        {{ $settings->home_hero_subtitle ?? "We’re building a Laravel-powered clone of eelstore.pk – complete with categories, product listings, wire & cable estimations, cart, wishlist and more." }}
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        <a href="{{ route('shop.index') }}"
                           class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-amber-400 text-slate-900 font-semibold text-sm sm:text-base hover:bg-amber-300 transition">
                            Shop Now
                        </a>
                        {{-- second button removed --}}
                    </div>
                </div>

                {{-- Right: responsive slider --}}
                <div class="mt-6 lg:mt-0">
                    <div
                        class="relative h-56 sm:h-64 md:h-72 lg:h-80 xl:h-96 rounded-3xl overflow-hidden bg-gradient-to-br from-amber-400 to-pink-500"
                        data-hero-slider
                    >
                        @php $hasSlides = isset($heroSliders) && $heroSliders->count() > 0; @endphp

                        @if($hasSlides)
                            @foreach($heroSliders as $index => $slide)
                                <div
                                    class="absolute inset-0 transition-opacity duration-700 ease-in-out
                                    {{ $index === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
                                    data-hero-slide
                                >
                                    <img
                                        src="{{ asset($slide->image_path) }}"
                                        alt="{{ $slide->title ?? 'Homepage slide '.($index + 1) }}"
                                        class="w-full h-full object-cover"
                                    >
                                </div>
                            @endforeach
                        @else
                            {{-- Fallback if no slides yet --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div
                                        class="mx-auto mb-4 w-12 h-12 rounded-full bg-amber-300/90 flex items-center justify-center text-2xl">
                                        ⚡
                                    </div>
                                    <p class="text-sm text-slate-100/80">
                                        Add homepage slider images in the admin panel later.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($hasSlides)
                        <div class="flex justify-center gap-2 mt-3">
                            @foreach($heroSliders as $index => $slide)
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-600"
                                      data-hero-dot="{{ $index }}"></span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>


    {{-- Service icons row (Delivery, Return, Quality, Support) --}}
    <section class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">🚚</div>
                <div>
                    <div class="font-semibold">Delivery in 24H</div>
                    <div class="text-gray-500 text-xs">Free shipping over Rs. 10,000</div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">↩</div>
                <div>
                    <div class="font-semibold">24 Hours Return</div>
                    <div class="text-gray-500 text-xs">Free return over Rs. 10,000</div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">✔</div>
                <div>
                    <div class="font-semibold">Quality Guarantee</div>
                    <div class="text-gray-500 text-xs">Quality checked by our team</div>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">📞</div>
                <div>
                    <div class="font-semibold">Support 24/7</div>
                    <div class="text-gray-500 text-xs">Shop with an expert</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured Wires (grouped by company) --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
            <h2 class="text-xl font-semibold">Wires (Featured)</h2>
            <a href="{{ route('category.show', 'wires') }}" class="text-xs text-amber-600 hover:text-amber-500">
                View all wires →
            </a>
        </div>

        @if($featuredWires->isEmpty())
            <p class="text-sm text-gray-600">No featured wires yet.</p>
        @else
            @php
                $grouped = $featuredWires->groupBy(fn($p) => optional($p->company)->name ?? 'Other');
            @endphp

            @foreach($grouped as $companyName => $products)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold mb-2 text-gray-700">{{ $companyName }}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                        @foreach($products as $product)
                            @include('partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </section>

    {{-- Featured Lighting --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
        <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
            <h2 class="text-xl font-semibold">Lighting (Featured)</h2>
            <a href="{{ route('category.show', 'lighting') }}" class="text-xs text-amber-600 hover:text-amber-500">
                View all lighting →
            </a>
        </div>

        @if($featuredLighting->isEmpty())
            <p class="text-sm text-gray-600">No featured lighting products yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($featuredLighting as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>

@endsection
