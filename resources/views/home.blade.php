@extends('layouts.frontend')

@section('title', 'Home – ' . config('app.name'))

@section('content')
    {{-- Hero section (we’ll refine later to match EELStore banners) --}}
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight mb-4">
                    Premium Electrical Supplies &amp; Cables
                </h1>
                <p class="text-sm sm:text-base text-gray-300 mb-6">
                    We’re building a Laravel-powered clone of eelstore.pk – complete with categories,
                    product listings, wire &amp; cable estimations, cart, wishlist and more.
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="#" class="inline-flex items-center px-5 py-2.5 rounded-md bg-amber-400 text-slate-900 font-semibold text-sm hover:bg-amber-300">
                        Shop Now
                    </a>
                    <a href="#" class="inline-flex items-center px-5 py-2.5 rounded-md border border-slate-600 text-sm hover:border-amber-400 hover:text-amber-400">
                        View Wire &amp; Cable Estimations
                    </a>
                </div>
            </div>

            <div class="relative">
                <div class="w-full h-56 sm:h-64 rounded-xl bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500 shadow-lg flex items-center justify-center">
                    <span class="text-5xl">⚡</span>
                </div>
                <p class="mt-3 text-xs text-gray-300">
                    This is placeholder artwork – we’ll replace it with a slider/banner similar to EELStore’s hero later.
                </p>
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
