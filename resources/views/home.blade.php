@extends('layouts.frontend')

@php use App\Support\Content; @endphp

@section('title', Content::text('seo.home_title'))

@push('meta')
    {!! \App\Support\StructuredData::script(\App\Support\StructuredData::store()) !!}
@endpush

@section('content')
    @php
        $buttons = collect([
            ['label' => content('home.hero_button_label'), 'url' => Content::link(content('home.hero_button_url')), 'primary' => true],
            ['label' => content('home.hero_button2_label'), 'url' => Content::link(content('home.hero_button2_url')), 'primary' => false],
        ])->filter(fn ($b) => filled($b['label']) && $b['url']);

        $benefits = collect(content('home.benefits', []))->filter(fn ($b) => filled($b['title'] ?? null));
        $hasSlides = isset($heroSliders) && $heroSliders->count() > 0;
    @endphp

    {{-- Hero section --}}
    <section class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                <div class="space-y-6">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight">
                        {{ Content::text('home.hero_title') }}
                    </h1>

                    @if(content('home.hero_subtitle'))
                        <p class="text-sm sm:text-base text-gray-300 max-w-xl">
                            {{ Content::text('home.hero_subtitle') }}
                        </p>
                    @endif

                    @if($buttons->isNotEmpty())
                        <div class="flex flex-wrap items-center gap-4 pt-2">
                            @foreach($buttons as $button)
                                <a href="{{ $button['url'] }}"
                                   class="{{ $button['primary']
                                        ? 'bg-accent text-on-accent hover:bg-accent/80'
                                        : 'border border-white/40 text-white hover:bg-white/10' }}
                                        inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold text-sm sm:text-base transition">
                                    {{ $button['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Slider (Website → Home slider) --}}
                <div class="mt-6 lg:mt-0">
                    <div class="relative h-56 sm:h-64 md:h-72 lg:h-80 xl:h-96 rounded-3xl overflow-hidden bg-gradient-to-br from-accent to-brand"
                         data-hero-slider>
                        @if($hasSlides)
                            @foreach($heroSliders as $index => $slide)
                                <div class="absolute inset-0 transition-opacity duration-700 ease-in-out
                                            {{ $index === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
                                     data-hero-slide>
                                    <x-store-image :src="$slide->imageUrl()"
                                                   :alt="$slide->title ?: Content::text('home.hero_title')"
                                                   :lazy="$index > 0"
                                                   class="w-full h-full object-cover"/>
                                </div>
                            @endforeach
                        @else
                            {{-- No slides yet: placeholder image (+ optional text from Website → Home page) --}}
                            <x-store-image :alt="Content::text('home.hero_title')" :lazy="false" class="absolute inset-0 w-full h-full object-cover"/>
                            @if(content('home.hero_empty_text'))
                                <p class="absolute inset-x-0 bottom-4 text-center text-sm text-slate-600 px-6">{{ Content::text('home.hero_empty_text') }}</p>
                            @endif
                        @endif
                    </div>

                    @if($hasSlides && $heroSliders->count() > 1)
                        <div class="flex justify-center gap-2 mt-3">
                            @foreach($heroSliders as $index => $slide)
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-600" data-hero-dot="{{ $index }}"></span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Store benefits strip (Website → Home page) --}}
    @if(content('home.benefits_enabled') && $benefits->isNotEmpty())
        <section class="bg-white border-t border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="grid grid-cols-2 {{ $benefits->count() >= 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-x-4 gap-y-5 sm:gap-6">
                    @foreach($benefits as $benefit)
                        <div class="flex items-start gap-2.5 sm:gap-3">
                            <div class="flex-shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-brand text-accent flex items-center justify-center">
                                <x-store-icon :name="$benefit['icon'] ?? 'check'" class="w-4 h-4 sm:w-5 sm:h-5"/>
                            </div>
                            <div>
                                <div class="font-semibold text-[13px] sm:text-base text-slate-900 leading-tight">{{ $benefit['title'] }}</div>
                                @if(filled($benefit['text'] ?? null))
                                    <div class="text-xs sm:text-sm text-slate-500">{{ $benefit['text'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Product blocks (Website → Home page) --}}
    @include('partials.product-row', ['title' => Content::text('home.deals_title'), 'products' => $deals, 'link' => route('shop.index', ['on_sale' => 1])])

    @include('partials.product-row', ['title' => Content::text('home.featured_title'), 'products' => $featured, 'link' => null])

    {{-- Shop by brand --}}
    @if($brands->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h2 class="text-xl font-semibold mb-4">{{ Content::text('home.brands_title') }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($brands as $brand)
                    <a href="{{ route('brand.show', $brand->slug) }}"
                       class="bg-white border border-gray-200 rounded-lg h-20 px-3 flex items-center justify-center text-center hover:border-accent hover:shadow-sm transition">
                        @if($brand->logo)
                            <x-store-image :src="$brand->imageUrl()" :alt="$brand->name" class="max-h-12 w-auto object-contain"/>
                        @else
                            <span class="font-semibold text-slate-700">{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @include('partials.product-row', ['title' => Content::text('home.new_title'), 'products' => $newArrivals, 'link' => route('shop.index', ['sort' => 'latest'])])

    {{-- Category rows (Website → Home sections) --}}
    @foreach($homepageCategorySections as $section)
        @include('partials.product-row', [
            'title'    => $section->category->name,
            'products' => $section->products,
            'link'     => route('category.show', $section->category->slug),
        ])
    @endforeach
@endsection
