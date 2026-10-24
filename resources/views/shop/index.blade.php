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
                            @if(! $loop->first) <span aria-hidden="true">/</span> @endif
                            <a href="{{ $url }}" class="hover:text-accent">{{ $label }}</a>
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

    @php
        // Active filters → removable chips ("Wires ✕", "On sale ✕" …). Category pages pre-select their own
        // category, so category chips are only shown on the plain shop page.
        $query = collect(request()->query())->except(['page'])->all();

        $without = function (string $key, $value = null) use ($query, $filterAction) {
            $q = $query;
            if ($value === null) {
                unset($q[$key]);
            } else {
                $q[$key] = array_values(array_diff((array) ($q[$key] ?? []), [(string) $value, (int) $value]));
                if (! $q[$key]) {
                    unset($q[$key]);
                }
            }
            return $filterAction . ($q ? '?' . http_build_query($q) : '');
        };

        $chips = [];
        if (! $currentCategory) {
            foreach ($categories->whereIn('id', $selectedCategories) as $cat) {
                $chips[] = [$cat->name, $without('categories', $cat->id)];
            }
        }
        if (! $currentBrand) {
            $companyNames = collect($categoryCompanies)->flatten(1)->pluck('name', 'id');
            foreach ($selectedCompanies as $companyId) {
                if ($companyNames->has((int) $companyId)) {
                    $chips[] = [$companyNames[(int) $companyId], $without('companies', $companyId)];
                }
            }
        }
        if ($onSale) {
            $chips[] = ['On sale', $without('on_sale')];
        }
        if ($currentMin > $minPrice || $currentMax < $maxPrice) {
            $chips[] = [rupees($currentMin) . ' – ' . number_format($currentMax), $filterAction . '?' . http_build_query(collect($query)->except(['min_price', 'max_price'])->all())];
        }

        $sortOptions = [
            'default'        => 'Name (A–Z)',
            'latest'         => 'Newest first',
            'price_low_high' => 'Price: low to high',
            'price_high_low' => 'Price: high to low',
        ];
    @endphp

    {{-- Filters + products --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" x-data="{ filtersOpen: false }">
        {{-- Phone: filter toggle + sort --}}
        <div class="md:hidden flex items-center gap-2 mb-4">
            <button type="button" x-on:click="filtersOpen = ! filtersOpen" :aria-expanded="filtersOpen"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-800">
                <x-store-icon name="filter" class="w-4 h-4"/>
                <span x-text="filtersOpen ? 'Hide filters' : 'Filters'">Filters</span>
                @if(count($chips))
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-accent text-on-accent text-[11px] font-bold">{{ count($chips) }}</span>
                @endif
            </button>
            <label class="sr-only" for="sort-mobile">Sort</label>
            <select id="sort-mobile" name="sort" form="shop-filters-form" data-sync-submit
                    class="flex-1 rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-accent">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 md:gap-8">
            {{-- Filters (sidebar on desktop, collapsible on phones) --}}
            <aside class="md:col-span-1 hidden md:block" :class="{ '!block': filtersOpen }">
                <form
                    id="shop-filters-form"
                    method="GET"
                    action="{{ $filterAction }}"
                    class="bg-white border border-gray-200 rounded-xl px-4 py-5 space-y-6 shadow-sm md:sticky md:top-4"
                    data-shop-filters
                >
                    {{-- Categories + nested brands --}}
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">Categories</h2>

                        <div class="space-y-1.5 text-sm text-gray-700">
                            @foreach($categories as $category)
                                <label class="flex items-center gap-2 cursor-pointer py-0.5 hover:text-accent-ink">
                                    <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                           class="w-4 h-4 border-gray-300 rounded text-brand focus:ring-accent category-checkbox"
                                           data-category-id="{{ $category->id }}"
                                           @checked(in_array($category->id, $selectedCategories))>
                                    <span class="flex-1">{{ $category->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $category->products_count }}</span>
                                </label>

                                @if(! empty($categoryCompanies[$category->id]))
                                    <div class="space-y-1 text-xs text-gray-600 pl-6">
                                        @foreach($categoryCompanies[$category->id] as $company)
                                            <label class="flex items-center gap-2 cursor-pointer py-0.5 hover:text-accent-ink">
                                                <input type="checkbox" name="companies[]" value="{{ $company['id'] }}"
                                                       class="w-3.5 h-3.5 border-gray-300 rounded text-brand focus:ring-accent company-checkbox"
                                                       data-category-id="{{ $category->id }}"
                                                       @checked(in_array($company['id'], $selectedCompanies))>
                                                <span class="flex-1">{{ $company['name'] }}</span>
                                                <span class="text-[11px] text-gray-400">{{ $company['count'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- On sale --}}
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="on_sale" value="1" class="w-4 h-4 border-gray-300 rounded text-brand focus:ring-accent" @checked($onSale)>
                            <span>Only products on sale</span>
                        </label>
                    </div>

                    {{-- Price --}}
                    @if($maxPrice > $minPrice)
                        <div class="border-t border-gray-100 pt-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-4">Price</h3>
                            <div class="px-2">
                                <div id="price-slider" class="mb-3"
                                     data-min="{{ $minPrice }}" data-max="{{ $maxPrice }}"
                                     data-current-min="{{ $currentMin }}" data-current-max="{{ $currentMax }}"></div>
                            </div>
                            <p class="text-xs text-gray-600">
                                Rs <span id="price-min-label">{{ number_format($currentMin) }}</span>
                                – Rs <span id="price-max-label">{{ number_format($currentMax) }}</span>
                            </p>
                            <input type="hidden" name="min_price" id="min_price_input" value="{{ $currentMin }}">
                            <input type="hidden" name="max_price" id="max_price_input" value="{{ $currentMax }}">
                        </div>
                    @endif

                    <div class="flex items-center gap-2 border-t border-gray-100 pt-4">
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-md bg-accent text-on-accent text-sm font-semibold hover:bg-accent/80 shadow-sm">
                            Apply filters
                        </button>
                        @if(count($chips))
                            <a href="{{ $filterAction }}" class="px-3 py-2.5 text-sm text-gray-600 hover:text-red-600">Clear</a>
                        @endif
                    </div>
                </form>
            </aside>

            {{-- Products --}}
            <div class="md:col-span-3 space-y-4">
                {{-- Toolbar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-gray-200 rounded-md px-4 py-3 shadow-sm">
                    <p class="text-sm text-gray-600">
                        @if($products->total() > 0)
                            <span class="font-semibold text-slate-900">{{ $products->total() }}</span> {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                            @if($products->lastPage() > 1)
                                <span class="text-gray-400">· showing {{ $products->firstItem() }}–{{ $products->lastItem() }}</span>
                            @endif
                        @else
                            <span class="font-semibold text-slate-900">0</span> products
                        @endif
                    </p>

                    <div class="hidden md:flex items-center gap-3 text-sm">
                        <label class="flex items-center gap-2 text-xs text-gray-500">
                            Sort
                            <select name="sort" form="shop-filters-form" data-sync-submit
                                    class="border border-gray-300 rounded-md pl-3 pr-8 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-accent">
                                @foreach($sortOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="flex items-center gap-2 text-xs text-gray-500">
                            Show
                            <select name="per_page" form="shop-filters-form" data-sync-submit
                                    class="border border-gray-300 rounded-md pl-3 pr-8 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-accent">
                                @foreach([12, 20, 40, 60] as $size)
                                    <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                </div>

                {{-- Active filter chips --}}
                @if(count($chips))
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($chips as [$label, $removeUrl])
                            <a href="{{ $removeUrl }}"
                               class="inline-flex items-center gap-1.5 rounded-full border border-accent/50 bg-accent/10 px-3 py-1 text-xs font-medium text-slate-800 hover:bg-accent/20">
                                {{ $label }}
                                <x-store-icon name="close" class="w-3 h-3" aria-label="Remove"/>
                            </a>
                        @endforeach
                        <a href="{{ $filterAction }}" class="text-xs text-gray-500 hover:text-red-600 underline ml-1">Clear all</a>
                    </div>
                @endif

                @if($products->isEmpty())
                    <div class="bg-white border border-gray-200 rounded-lg p-8 text-center">
                        <x-store-icon name="search" class="w-10 h-10 mx-auto text-gray-300"/>
                        <p class="mt-3 text-sm text-gray-700">{{ \App\Support\Content::text('pages.shop_empty') }}</p>
                        <p class="mt-1 text-xs text-gray-500">Try removing a filter, or ask us on WhatsApp – we may have it in the shop.</p>
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <a href="{{ $filterAction }}" class="inline-flex items-center px-4 py-2 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">Clear filters</a>
                            <a href="{{ route('quotes.create') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-brand text-brand text-sm font-semibold hover:bg-brand hover:text-white">Request a quote</a>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6 items-stretch">
                        @foreach($products as $product)
                            @include('partials.product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $products->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
