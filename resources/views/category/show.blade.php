@extends('layouts.frontend')

@section('title', $category->name . ' – ' . config('app.name'))

@section('content')
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">{{ $category->name }}</h1>
            @if($category->description)
                <p class="mt-2 text-sm text-gray-300">{{ $category->description }}</p>
            @endif
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($products->isEmpty())
            <p class="text-sm text-gray-600">
                No products found in this category yet.
            </p>
        @else
            @if ($hasCompanies)
                {{-- Category like "Wires" with companies --}}
                @foreach ($products->groupBy(fn($p) => optional($p->company)->name ?? 'Other') as $companyName => $companyProducts)
                    <div class="mb-8">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-lg font-semibold">
                                {{ $companyName }}
                            </h2>
                            <span class="text-xs text-gray-500">
                                {{ $companyProducts->count() }} product(s)
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach ($companyProducts as $product)
                                @include('partials.product-card', ['product' => $product])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                {{-- Simple grid (e.g. Lighting) --}}
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold">
                        {{ $category->name }} Products
                    </h2>
                    <span class="text-xs text-gray-500">
                        {{ $products->count() }} product(s)
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        @endif
    </section>
@endsection
