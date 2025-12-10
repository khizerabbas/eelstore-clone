@extends('layouts.frontend')

@section('title', 'Search – ' . config('app.name'))

@section('content')
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold mb-2">Search</h1>
            <p class="text-sm text-gray-300">
                @if($query)
                    Results for: <span class="font-semibold">“{{ $query }}”</span>
                @else
                    Enter a term in the search box to find products.
                @endif
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(!$query)
            <p class="text-sm text-gray-600">
                Use the search bar in the header to look for products by name or description.
            </p>
        @else
            @if($products->isEmpty())
                <p class="text-sm text-gray-600">
                    No products found matching “{{ $query }}”.
                </p>
            @else
                <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
                    <p class="text-sm text-gray-600">
                        Found {{ $products->total() }} product(s).
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    @foreach($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                {{ $products->links() }}
            @endif
        @endif
    </section>
@endsection
