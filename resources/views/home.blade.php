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
    {{-- Store benefits strip --}}
    {{-- Store benefits strip --}}
    <section class="bg-white border-t border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Genuine Products --}}
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center">
                        {{-- Shield icon --}}
                        <svg class="w-5 h-5 text-amber-300" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3L5 6V11C5 15.52 8.06 19.69 12 21
                                 C15.94 19.69 19 15.52 19 11V6L12 3Z"
                                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M9 12L11 14L15 10"
                                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-sm sm:text-base text-slate-900">
                            Genuine Products
                        </div>
                        <div class="text-xs sm:text-sm text-slate-500">
                            100% original branded items
                        </div>
                    </div>
                </div>

                {{-- Best Prices Guaranteed --}}
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center">
                        {{-- Tag icon --}}
                        <svg class="w-5 h-5 text-emerald-300" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 10V5.5C4 4.67 4.67 4 5.5 4H10L18 12L12 18L4 10Z"
                                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <circle cx="8" cy="8" r="1.1" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-sm sm:text-base text-slate-900">
                            Best Prices Guaranteed
                        </div>
                        <div class="text-xs sm:text-sm text-slate-500">
                            Competitive pricing on all items
                        </div>
                    </div>
                </div>

                {{-- Quality Guarantee --}}
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center">
                        <svg class="w-6 h-6 text-violet-300" viewBox="1 0 20 18" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 3L14.09 5.26L17.31 5.8L16 8.39L16.45 11.64L13.8 12.76
                 L12 15L10.2 12.76L7.55 11.64L8 8.39L6.69 5.8L9.91 5.26L12 3Z"
                                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                            <path d="M9.5 11.5L11 13L14 10"
                                  stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <div>
                        <div class="font-semibold text-sm sm:text-base text-slate-900">
                            Quality Guarantee
                        </div>
                        <div class="text-xs sm:text-sm text-slate-500">
                            Quality checked by our team
                        </div>
                    </div>
                </div>

                {{-- Support 24/7 --}}
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-slate-900 flex items-center justify-center">
                        {{-- Phone icon (fixed) --}}
                        <svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" width="24" height="24"
                             style="shape-rendering:geometricPrecision;text-rendering:geometricPrecision;image-rendering:optimizeQuality;fill-rule:evenodd;clip-rule:evenodd"
                             viewBox="0 0 6.827 6.827"><defs>
                                <style>.fil0 {
                                        fill: none
                                    }

                                    .fil2 {
                                        fill: #e64a19
                                    }</style>
                            </defs>
                            <g id="Layer_x0020_1">
                                <g id="_491463032">
                                    <path id="_491463320" class="fil0" d="M0 0h6.827v6.827H0z"/>
                                    <path id="_491463128" class="fil0" d="M.853.853h5.12v5.12H.853z"/>
                                </g>
                                <g id="_491478824">
                                    <path id="_491463224"
                                          d="M.909 2.24c.067 1.39 1.968 3.158 3.255 3.57.863.275 2.148-.269 1.64-.777L5 4.23c-.122-.123-.32-.108-.439.01l-.46.462c-.992-.54-1.408-.966-1.953-1.951l.462-.462c.119-.119.132-.317.01-.439l-.803-.803C1.37.598.883 1.715.908 2.24z"
                                          style="fill:#ff6e40"/>
                                    <path id="_491478584" class="fil2"
                                          d="m.909 2.24 1.24.51.462-.46c.119-.12.132-.318.01-.44l-.803-.803C1.37.598.883 1.715.908 2.24z"/>
                                    <path id="_491478176" class="fil2"
                                          d="M4.164 5.81c.863.275 2.148-.269 1.64-.777L5 4.23c-.122-.123-.32-.108-.439.01l-.46.462.062 1.107z"/>
                                </g>
                            </g></svg>
                    </div>
                    <div>
                        <div class="font-semibold text-sm sm:text-base text-slate-900">
                            Support 24/7
                        </div>
                        <div class="text-xs sm:text-sm text-slate-500">
                            Shop with an expert
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- Featured Wires (grouped by company) --}}
    @foreach($homepageCategorySections as $section)
        @php
            $category = $section->category;
            $products = $section->products;
        @endphp
        @if($category && $products->isNotEmpty())
            <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div
                    class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
                    <h2 class="text-xl font-semibold"> {{ $category->name }}</h2>
                    <a href="{{ route('category.show', $category->slug) }}"
                       class="text-xs text-amber-600 hover:text-amber-500">
                        View all →
                    </a>
                </div>
                <div class="mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                        @foreach($products as $product)
                            @include('partials.product-card', ['product' => $product])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach
@endsection
