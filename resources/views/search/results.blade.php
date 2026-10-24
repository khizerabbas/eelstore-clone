@extends('layouts.frontend')

@php use App\Support\Content; @endphp

@section('title', ($query ? '“' . $query . '” – ' : '') . 'Search' . Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex, follow">
@endpush

@section('content')
    <section class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold mb-3">Search</h1>

            {{-- Search box on the page itself (the header search is hidden behind an icon on phones) --}}
            <form action="{{ route('search') }}" method="GET" role="search" class="flex gap-2 max-w-2xl">
                <input type="search" name="q" value="{{ $query }}" placeholder="{{ content('header.search_placeholder') }}"
                       aria-label="{{ content('header.search_placeholder') }}" @if($query === '') autofocus @endif
                       class="flex-1 min-w-0 rounded-md border border-white/20 bg-white/10 px-3 py-2.5 text-base placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-accent">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-md bg-accent text-on-accent text-sm font-semibold hover:bg-accent/80">
                    <x-store-icon name="search" class="w-4 h-4"/> <span class="hidden sm:inline">Search</span>
                </button>
            </form>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @if($query === '')
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <p class="text-sm text-gray-700">Type a product name, size or brand – for example <em>2.5mm wire</em>, <em>LED bulb</em> or a brand name.</p>
                @include('search.partials.browse-categories')
            </div>
        @elseif($products->isEmpty())
            <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                <x-store-icon name="search" class="w-10 h-10 mx-auto text-gray-300"/>
                <p class="mt-3 text-sm font-medium text-gray-800">{{ Content::text('pages.search_empty') }}</p>
                <p class="mt-1 text-xs text-gray-500">Check the spelling, try fewer words, or ask us – we may have it in the shop.</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    @php
                        $askWhatsApp = \App\Models\SiteSetting::current()->whatsappLink('Assalam o Alaikum, do you have: ' . $query . '?');
                    @endphp
                    @if($askWhatsApp)
                        <a href="{{ $askWhatsApp }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            <x-store-icon name="whatsapp" class="w-4 h-4"/> Ask on WhatsApp
                        </a>
                    @endif
                    <a href="{{ route('quotes.create') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white">
                        Request a quote
                    </a>
                </div>
                @include('search.partials.browse-categories')
            </div>
        @else
            <p class="mb-4 text-sm text-gray-600">
                <span class="font-semibold text-slate-900">{{ $products->total() }}</span>
                {{ \Illuminate\Support\Str::plural('result', $products->total()) }} for <span class="font-semibold text-slate-900">“{{ $query }}”</span>
            </p>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6 items-stretch">
                @foreach($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-6">
                {{ $products->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
@endsection
