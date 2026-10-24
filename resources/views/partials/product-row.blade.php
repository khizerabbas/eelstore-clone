{{-- A titled row of product cards (home page blocks and category sections) --}}
@if($products->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
            <h2 class="text-xl font-semibold">{{ $title }}</h2>
            @if($link)
                <a href="{{ $link }}" class="text-xs text-accent-ink hover:underline">{{ content('home.section_view_all') }}</a>
            @endif
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-stretch">
            @foreach($products as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
@endif
