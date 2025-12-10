@extends('layouts.frontend')

@section('title', 'Wishlist – ' . config('app.name'))

@section('content')
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">Wishlist</h1>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(session('success'))
            <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-3 py-2">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                {{ session('error') }}
            </div>
        @endif

        @if($products->isEmpty())
            <p class="text-sm text-gray-600">
                Your wishlist is currently empty.
            </p>
            <div class="mt-4">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center justify-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                    Browse Products
                </a>
            </div>
        @else
            <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
                <p class="text-sm text-gray-600">
                    You have {{ $products->count() }} product(s) in your wishlist.
                </p>

                <form action="{{ route('wishlist.clear') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="text-xs px-3 py-1 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-100"
                    >
                        Clear Wishlist
                    </button>
                </form>
            </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                @foreach($products as $product)
                    <div class="relative">
                        {{-- Your existing remove-from-wishlist X button at top-right --}}
                        {{-- e.g. <form action="{{ route('wishlist.remove', $product) }}">...X...</form> --}}

                        @include('partials.product-card', [
                            'product' => $product,
                            'showWishlistIcon' => false, // 👈 hide heart on wishlist page
                        ])
                    </div>
                @endforeach

            </div>
        @endif
    </section>
@endsection
