@extends('layouts.frontend')

@php
    use App\Support\Content;
    use App\Support\StructuredData;
    use Illuminate\Support\Str;

    // Plain shop, a category page (/category/{slug}) or a brand page (/brand/{slug})
    $currentCategory ??= null;
    $currentBrand ??= null;

    if ($currentCategory) {
        $heading      = $currentCategory->name;
        $intro        = $currentCategory->description;
        $pageTitle    = $currentCategory->meta_title ?: $currentCategory->name;
        $pageDesc     = $currentCategory->meta_description ?: Str::limit((string) $currentCategory->description, 155);
        $filterAction = route('category.show', $currentCategory->slug);
        $crumbs       = StructuredData::categoryCrumbs($currentCategory);
    } elseif ($currentBrand) {
        $heading      = $currentBrand->name;
        $intro        = $currentBrand->description;
        $pageTitle    = $currentBrand->name;
        $pageDesc     = Str::limit((string) ($currentBrand->description ?: $currentBrand->name . ' products at ' . \App\Models\SiteSetting::storeName()), 155);
        $filterAction = route('brand.show', $currentBrand->slug);
        $crumbs       = [[content('header.nav_home'), route('home')], [Content::text('pages.shop_title'), route('shop.index')], [$currentBrand->name, $filterAction]];
    } else {
        $heading      = Content::text('pages.shop_title');
        $intro        = Content::text('pages.shop_intro');
        $pageTitle    = $heading;
        $pageDesc     = null;
        $filterAction = route('shop.index');
        $crumbs       = null;
    }
@endphp

@section('title', $pageTitle . Content::text('seo.title_suffix'))
@if($pageDesc)
    @section('meta_description', $pageDesc)
@endif

@push('meta')
    @if($crumbs)
        {!! StructuredData::script(StructuredData::breadcrumbs($crumbs)) !!}
    @endif
    {{-- Filtered/sorted/paged variants are not separate pages for Google --}}
    @if(filled(request()->server('QUERY_STRING')))
        <meta name="robots" content="noindex, follow">
    @endif
@endpush

@section('content')
    {{-- Header --}}
    <section class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
            <div>
                @if($crumbs)
                    <nav class="text-xs text-gray-400 mb-1" aria-label="Breadcrumb">
                        @foreach(array_slice($crumbs, 0, -1) as [$label, $url])
                            <a href="{{ $url }}" class="hover:text-accent">{{ $label }}</a> /
                        @endforeach
                    </nav>
                @endif
                <div class="flex items-center gap-3">
                    @if($currentBrand?->logo)
                        <span class="bg-white rounded-md p-1.5 h-12 flex items-center">
                            <x-store-image :src="$currentBrand->imageUrl()" :alt="$currentBrand->name" class="max-h-9 w-auto object-contain"/>
                        </span>
                    @endif
                    <h1 class="text-2xl font-bold">{{ $heading }}</h1>
                </div>
                @if($intro)
                    <p class="text-sm text-gray-300 mt-1 max-w-3xl">{{ $intro }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Filters + products --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            {{-- Sidebar filters form --}}
            <aside class="md:col-span-1">
                <form
                    id="shop-filters-form"
                    method="GET"
                    action="{{ $filterAction }}"
                    class="bg-white border border-gray-200 rounded-xl px-4 py-5 space-y-6 shadow-sm"
                >
                    {{-- SHOP BY CATEGORIES + nested companies --}}
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">
                            Shop by Categories
                        </h2>

                        <div class="space-y-2 text-sm text-gray-700">
                            @foreach($categories as $category)
                                {{-- Top-level category row --}}
                                <label class="flex items-center gap-2 cursor-pointer hover:text-accent-ink">
                                    <input
                                        type="checkbox"
                                        name="categories[]"
                                        value="{{ $category->id }}"
                                        class="w-4 h-4 border-gray-300 rounded category-checkbox"
                                        data-category-id="{{ $category->id }}"
                                        {{ in_array($category->id, $selectedCategories) ? 'checked' : '' }}
                                    >
                                    <span>
                                    {{ $category->name }}
                                    <span class="text-xs text-gray-500">
                                        ({{ $category->products_count }})
                                    </span>
                                </span>
                                </label>

                                {{-- Nested companies under this category, if any --}}
                                @if(!empty($categoryCompanies[$category->id]))
                                    <div class="mt-1 mb-1 space-y-1 text-xs text-gray-600 pl-6">
                                        @foreach($categoryCompanies[$category->id] as $company)
                                            <label class="flex items-center gap-2 cursor-pointer hover:text-accent-ink">
                                                <span class="text-gray-400 text-sm leading-none">›</span>
                                                <input
                                                    type="checkbox"
                                                    name="companies[]"
                                                    value="{{ $company['id'] }}"
                                                    class="w-3 h-3 border-gray-300 rounded company-checkbox"
                                                    data-category-id="{{ $category->id }}"
                                                    {{ in_array($company['id'], $selectedCompanies) ? 'checked' : '' }}
                                                >
                                                <span>
                                                {{ $company['name'] }}
                                                <span class="text-[11px] text-gray-400">
                                                    ({{ $company['count'] }})
                                                </span>
                                            </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- OTHER FILTERS (on sale) --}}
                    <div>
                        <hr class="border-gray-200 mt-2 mb-1">

                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">
                            Other Filters
                        </h3>

                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input
                                type="checkbox"
                                name="on_sale"
                                value="1"
                                class="w-4 h-4 border-gray-300 rounded"
                                {{ $onSale ? 'checked' : '' }}
                            >
                            <span>Show only products on sale</span>
                        </label>
                    </div>

                    {{-- PRICE with slider --}}
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">
                            Price
                        </h3>

                        <div class="px-2">
                            <div
                                id="price-slider"
                                class="mb-3"
                                data-min="{{ $minPrice }}"
                                data-max="{{ $maxPrice }}"
                                data-current-min="{{ $currentMin }}"
                                data-current-max="{{ $currentMax }}"
                            ></div>
                        </div>


                        <p class="text-[11px] text-gray-500">
                            Price: Rs <span id="price-min-label">{{ number_format($currentMin, 0) }}</span>
                            – Rs <span id="price-max-label">{{ number_format($currentMax, 0) }}</span>
                        </p>

                        {{-- Hidden inputs submitted with this form --}}
                        <input type="hidden" name="min_price" id="min_price_input" value="{{ $currentMin }}">
                        <input type="hidden" name="max_price" id="max_price_input" value="{{ $currentMax }}">

                        <button
                            type="submit"
                            class="mt-3 inline-flex items-center px-4 py-1.5 rounded-md bg-accent text-on-accent text-xs font-semibold hover:bg-accent/80 shadow-sm"
                        >
                            Filter
                        </button>
                    </div>
                </form>
            </aside>

            {{-- Products + top controls (NOT in the form) --}}
            <div class="md:col-span-3 space-y-4">
                {{-- Top controls --}}
                <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
                    <div class="text-sm text-gray-600">
                        @if($products->total() > 0)
                            Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
                            of {{ $products->total() }} product(s)
                        @else
                            {{ \App\Support\Content::text('pages.shop_empty') }}
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        {{-- Show per page --}}
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600 text-xs uppercase tracking-wide">Show</span>
                            <div class="relative">
                                <select
                                    name="per_page"
                                    form="shop-filters-form"
                                    class="select-clean border border-gray-300 rounded-md px-3 pr-8 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-accent"
                                    onchange="this.form.submit()"
                                >
                                    @foreach([12, 20, 40, 60] as $size)
                                        <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>
                                            {{ $size }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400 text-xs">
                                ▾
                            </span>
                            </div>
                        </div>

                        {{-- Sort --}}
                        <div class="flex items-center gap-2">
                            <span class="sr-only">Sort</span>
                            <div class="relative">
                                <select
                                    name="sort"
                                    form="shop-filters-form"
                                    class="select-clean border border-gray-300 rounded-md px-3 pr-8 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-accent"
                                    onchange="this.form.submit()"
                                >
                                    <option value="default" {{ $sort === 'default' ? 'selected' : '' }}>
                                        Default sorting
                                    </option>
                                    <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>
                                        Sort by latest
                                    </option>
                                    <option value="price_low_high" {{ $sort === 'price_low_high' ? 'selected' : '' }}>
                                        Sort by price: low to high
                                    </option>
                                    <option value="price_high_low" {{ $sort === 'price_high_low' ? 'selected' : '' }}>
                                        Sort by price: high to low
                                    </option>
                                </select>
                                <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400 text-xs">
                                ▾
                            </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product grid --}}
                @if($products->isEmpty())
                    <p class="text-sm text-gray-600">
                        Try adjusting your filters or selecting different categories.
                    </p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                        @foreach($products as $product)
                            @include('partials.product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>

@endsection
