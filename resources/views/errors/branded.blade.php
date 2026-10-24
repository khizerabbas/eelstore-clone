{{-- Shared look for customer-facing error pages that can use the normal store layout (404, 403, 419, 429) --}}
@extends('layouts.frontend')

@section('title', $heading . \App\Support\Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 text-center">
        <p class="text-5xl sm:text-6xl font-extrabold text-accent-ink/80">{{ $code }}</p>
        <h1 class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900">{{ $heading }}</h1>
        <p class="mt-3 text-sm sm:text-base text-gray-600">{{ $message }}</p>

        @if($showSearch ?? false)
            <form action="{{ route('search') }}" method="GET" role="search" class="mt-6 flex gap-2 max-w-md mx-auto">
                <input type="search" name="q" placeholder="{{ content('header.search_placeholder') }}" aria-label="{{ content('header.search_placeholder') }}"
                       class="flex-1 min-w-0 rounded-md border border-gray-300 px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-accent">
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">
                    <x-store-icon name="search" class="w-4 h-4"/> <span class="hidden sm:inline">Search</span>
                </button>
            </form>
        @endif

        <div class="mt-6 flex flex-wrap justify-center gap-2">
            @if($showBack ?? false)
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}"
                   class="inline-flex items-center gap-1 px-4 py-2 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">
                    <x-store-icon name="arrow-left" class="w-4 h-4"/> Go back and try again
                </a>
            @endif
            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white">Home</a>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white">Shop</a>
            <a href="{{ route('contact.show') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white">Contact us</a>
        </div>
    </section>
@endsection
