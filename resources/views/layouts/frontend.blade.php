@php
    use App\Support\Content;

    // Everything below is editable in the admin panel (Website section) – see config/site_content.php
    $storeName     = $settings->store_name ?: config('app.name');
    $logoUrl       = Content::imageUrl('branding.logo');
    $faviconUrl    = Content::imageUrl('branding.favicon');
    $shareImageUrl = Content::imageUrl('seo.share_image') ?? $logoUrl;
    $metaDescription = trim($__env->yieldContent('meta_description')) ?: Content::text('seo.meta_description');

    $cartCount     = \App\Support\Cart::count();
    $wishlist      = session('wishlist', []);
    $wishlistCount = is_array($wishlist) ? count($wishlist) : 0;

    $showMyOrders = (bool) content('header.show_my_orders');
    $showWishlist = (bool) content('header.show_wishlist');

    $extraLinks = collect(content('header.extra_links', []))
        ->map(fn ($l) => ['label' => $l['label'] ?? '', 'url' => Content::link($l['url'] ?? null)])
        ->filter(fn ($l) => $l['label'] !== '' && $l['url']);

    $menuLinks = collect(content('footer.menu_links', []))
        ->map(fn ($l) => ['label' => $l['label'] ?? '', 'url' => Content::link($l['url'] ?? null)])
        ->filter(fn ($l) => $l['label'] !== '' && $l['url']);

    $socials = collect([
        'facebook'  => ['Facebook', $settings->facebook_url],
        'instagram' => ['Instagram', $settings->instagram_url],
        'youtube'   => ['YouTube', $settings->youtube_url],
        'x'         => ['X (Twitter)', $settings->x_url],
        'whatsapp'  => ['WhatsApp', $settings->whatsappLink()],
    ])->filter(fn ($s) => $s[1] && preg_match('/^https?:\/\//i', $s[1]));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="error-report-url" content="{{ route('client-errors.store') }}">
    <title>@yield('title', $storeName)</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Social sharing (WhatsApp, Facebook …) --}}
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ trim($__env->yieldContent('title', $storeName)) }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    @if($shareImageUrl)
        <meta property="og:image" content="{{ $shareImageUrl }}">
    @endif
    @if(content('seo.google_site_verification'))
        <meta name="google-site-verification" content="{{ content('seo.google_site_verification') }}">
    @endif
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    @stack('meta')
    @include('partials.analytics')

    <style>{!! \App\Support\Theme::cssVariables() !!}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-page">
<div id="toast"
     class="fixed bottom-4 right-4 z-50 px-4 py-2 rounded-md bg-brand text-white text-xs shadow-lg
            opacity-0 pointer-events-none transition-opacity duration-300 hidden">
    <!-- message injected by JS -->
</div>
<div class="min-h-screen flex flex-col">

    {{-- Top bar --}}
    @if(content('header.topbar_enabled'))
        <div class="w-full bg-brand text-gray-200 text-sm border-b border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center gap-4 min-h-9 py-1.5">
                <span class="text-xs sm:text-sm truncate">{{ Content::text('header.topbar_message') }}</span>

                @if($settings->contact_phone)
                    <span class="flex-shrink-0">
                        <span class="hidden sm:inline-block mr-1">{{ content('header.topbar_phone_label') }}</span>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->contact_phone) }}" class="font-medium hover:text-accent">
                            {{ $settings->contact_phone }}
                        </a>
                    </span>
                @endif
            </div>
        </div>
    @endif

    {{-- Main header --}}
    <header class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3 md:gap-4">
            {{-- Logo / brand --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-9 md:h-10 w-auto max-w-[150px] md:max-w-[180px] object-contain">
                @else
                    <span class="bg-accent text-on-accent w-9 h-9 md:w-10 md:h-10 flex-shrink-0 flex items-center justify-center rounded-full font-extrabold text-base md:text-lg">
                        {{ content('branding.logo_text') }}
                    </span>
                @endif
                @if(content('branding.show_name'))
                    <span class="leading-tight min-w-0">
                        <span class="block font-bold text-base md:text-lg tracking-wide truncate">{{ $storeName }}</span>
                        @if(content('branding.tagline'))
                            <span class="hidden md:block text-[11px] text-gray-300 truncate">{{ content('branding.tagline') }}</span>
                        @endif
                    </span>
                @endif
            </a>

            {{-- Category + search (desktop) --}}
            <div class="hidden md:flex flex-1 items-center gap-3 max-w-2xl">
                <select
                    name="category"
                    form="header-search-form"
                    aria-label="{{ content('header.search_all_label') }}"
                    class="bg-white/10 border border-white/20 text-sm rounded-md pl-3 pr-8 py-2 max-w-[11rem] focus:outline-none focus:ring-2 focus:ring-accent [&>option]:text-gray-900"
                >
                    <option value="">{{ content('header.search_all_label') }}</option>
                    @foreach($searchCategories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) request('category') === (string) $cat->id)>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <form id="header-search-form" action="{{ route('search') }}" method="GET" class="flex-1" role="search">
                    <div class="relative">
                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ content('header.search_placeholder') }}"
                            aria-label="{{ content('header.search_placeholder') }}"
                            class="w-full bg-white/10 border border-white/20 text-sm rounded-md pl-3 pr-10 py-2 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-accent"
                        >
                        <button type="submit" aria-label="Search"
                                class="absolute inset-y-0 right-0 px-3 flex items-center justify-center text-gray-300 hover:text-accent">
                            <x-store-icon name="search" class="w-4 h-4"/>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Desktop: my orders / wishlist / cart --}}
            <div class="hidden md:flex items-center gap-4 text-xs flex-shrink-0">
                @if($showMyOrders)
                    <a href="{{ route('orders.index') }}" class="flex items-center gap-1.5 hover:text-accent">
                        <x-store-icon name="box" class="w-5 h-5"/> {{ content('header.nav_my_orders') }}
                    </a>
                @endif

                @if($showWishlist)
                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-1.5 hover:text-accent">
                        <x-store-icon name="heart" class="w-5 h-5"/> {{ content('header.nav_wishlist') }}
                        <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1 rounded-full bg-accent text-on-accent text-xs font-semibold" id="wishlist-count" data-wishlist-count>{{ $wishlistCount }}</span>
                    </a>
                @endif

                <a href="{{ route('cart.index') }}" class="flex items-center gap-1.5 hover:text-accent">
                    <x-store-icon name="cart" class="w-5 h-5"/> {{ content('header.nav_cart') }}
                    <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1 rounded-full bg-accent text-on-accent text-xs font-semibold" id="cart-count" data-cart-count>{{ $cartCount }}</span>
                </a>
            </div>

            {{-- Phone / small tablet: search, cart (with count) and menu buttons --}}
            <div class="flex md:hidden items-center gap-1.5 flex-shrink-0">
                <button id="mobile-search-toggle" type="button" aria-label="Search" aria-controls="mobile-search-panel" aria-expanded="false"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-white/10 text-gray-100">
                    <x-store-icon name="search" class="w-5 h-5"/>
                </button>

                <a href="{{ route('cart.index') }}" aria-label="{{ content('header.nav_cart') }}"
                   class="relative inline-flex items-center justify-center w-10 h-10 rounded-md bg-white/10 text-gray-100">
                    <x-store-icon name="cart" class="w-5 h-5"/>
                    <span data-cart-count data-hide-when-empty
                          class="absolute -top-1.5 -right-1.5 min-w-[1.25rem] h-5 px-1 rounded-full bg-accent text-on-accent text-[11px] font-bold flex items-center justify-center {{ $cartCount ? '' : 'hidden' }}">{{ $cartCount }}</span>
                </a>

                <button id="mobile-menu-toggle" type="button" aria-label="Open main menu" aria-controls="mobile-menu-panel" aria-expanded="false"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-md bg-white/10 text-gray-100">
                    <x-store-icon name="menu" class="w-5 h-5"/>
                </button>
            </div>
        </div>

        {{-- Mobile search panel --}}
        <div id="mobile-search-panel" class="md:hidden hidden border-t border-white/10 px-4 py-3">
            <form action="{{ route('search') }}" method="GET" class="flex gap-2" role="search">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ content('header.search_placeholder') }}"
                       aria-label="{{ content('header.search_placeholder') }}"
                       class="flex-1 min-w-0 bg-white/10 border border-white/20 text-base rounded-md px-3 py-2 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-accent">
                <button type="submit" class="px-4 py-2 rounded-md bg-accent text-on-accent text-sm font-semibold">Search</button>
            </form>
        </div>

        {{-- Mobile nav panel --}}
        <div id="mobile-menu-panel" class="md:hidden hidden bg-brand text-gray-100 border-t border-white/10">
            <nav class="px-4 py-2 text-[15px]" aria-label="Mobile">
                <a href="{{ route('home') }}" class="flex items-center py-3 border-b border-white/10">{{ content('header.nav_home') }}</a>
                <a href="{{ route('shop.index') }}" class="flex items-center py-3 border-b border-white/10">{{ content('header.nav_shop') }}</a>

                <details class="border-b border-white/10 group">
                    <summary class="list-none flex items-center justify-between py-3 cursor-pointer">
                        <span>{{ content('header.nav_categories') }}</span>
                        <x-store-icon name="chevron-down" class="w-4 h-4 transition group-open:rotate-180"/>
                    </summary>
                    <div class="pb-2 pl-3 grid grid-cols-2 gap-x-3 text-sm text-gray-300">
                        @foreach($searchCategories as $category)
                            <a href="{{ route('category.show', $category->slug) }}" class="py-2 truncate hover:text-accent">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </details>

                @foreach($extraLinks as $link)
                    <a href="{{ $link['url'] }}" class="flex items-center py-3 border-b border-white/10">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('contact.show') }}" class="flex items-center py-3 border-b border-white/10">{{ content('header.nav_contact') }}</a>

                <div class="grid {{ [1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3'][1 + (int) $showMyOrders + (int) $showWishlist] }} gap-2 py-3 text-xs text-center">
                    @if($showMyOrders)
                        <a href="{{ route('orders.index') }}" class="flex flex-col items-center gap-1 rounded-md bg-white/10 py-2.5">
                            <x-store-icon name="box" class="w-5 h-5"/> {{ content('header.nav_my_orders') }}
                        </a>
                    @endif
                    @if($showWishlist)
                        <a href="{{ route('wishlist.index') }}" class="flex flex-col items-center gap-1 rounded-md bg-white/10 py-2.5">
                            <x-store-icon name="heart" class="w-5 h-5"/>
                            <span>{{ content('header.nav_wishlist') }} (<span id="wishlist-count-mobile" data-wishlist-count>{{ $wishlistCount }}</span>)</span>
                        </a>
                    @endif
                    <a href="{{ route('cart.index') }}" class="flex flex-col items-center gap-1 rounded-md bg-white/10 py-2.5">
                        <x-store-icon name="cart" class="w-5 h-5"/>
                        <span>{{ content('header.nav_cart') }} (<span id="cart-count-mobile" data-cart-count>{{ $cartCount }}</span>)</span>
                    </a>
                </div>

                @if($settings->contact_phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->contact_phone) }}"
                       class="mb-2 flex items-center justify-center gap-2 rounded-md bg-accent text-on-accent font-semibold py-2.5 text-sm">
                        <x-store-icon name="phone" class="w-4 h-4"/> {{ $settings->contact_phone }}
                    </a>
                @endif
            </nav>
        </div>

        {{-- Desktop main menu --}}
        <nav class="bg-brand text-gray-100 border-t border-white/10 hidden md:block" aria-label="Main">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-end h-12 gap-8 text-sm font-medium">
                    <a href="{{ route('home') }}" class="hover:text-accent">{{ content('header.nav_home') }}</a>

                    <div class="relative group">
                        <button type="button" class="inline-flex items-center gap-1 hover:text-accent">
                            <span>{{ content('header.nav_categories') }}</span>
                            <span class="text-xs">▾</span>
                        </button>
                        <div class="absolute left-1/2 top-full z-30 hidden w-56 -translate-x-1/2 rounded-md bg-brand shadow-lg ring-1 ring-black/5 group-hover:block group-focus-within:block">
                            <div class="py-1 max-h-80 overflow-y-auto text-sm">
                                @foreach($searchCategories as $category)
                                    <a href="{{ route('category.show', $category->slug) }}" class="block px-4 py-2 hover:bg-white/10">{{ $category->name }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('shop.index') }}" class="hover:text-accent">{{ content('header.nav_shop') }}</a>
                    @foreach($extraLinks as $link)
                        <a href="{{ $link['url'] }}" class="hover:text-accent">{{ $link['label'] }}</a>
                    @endforeach
                    <a href="{{ route('contact.show') }}" class="hover:text-accent">{{ content('header.nav_contact') }}</a>
                </div>
            </div>
        </nav>
    </header>

    {{-- Page content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-brand text-gray-300 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-2 {{ $footerPages->isNotEmpty() ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-x-6 gap-y-8">
            {{-- About --}}
            <div class="col-span-2 lg:col-span-1">
                <div class="flex items-center gap-2 mb-3">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-10 w-auto max-w-[160px] object-contain">
                    @else
                        <span class="bg-accent text-on-accent w-10 h-10 flex items-center justify-center rounded-full font-extrabold text-lg">{{ content('branding.logo_text') }}</span>
                    @endif
                    <div>
                        <div class="font-bold text-lg tracking-wide text-white">{{ $storeName }}</div>
                        @if(content('branding.tagline'))
                            <div class="text-xs text-gray-400">{{ content('branding.tagline') }}</div>
                        @endif
                    </div>
                </div>
                <p class="text-sm text-gray-400">{{ Content::text('footer.about_text') }}</p>
            </div>

            {{-- Menu --}}
            @if($menuLinks->isNotEmpty())
                <div>
                    <h3 class="font-semibold text-sm uppercase tracking-wide mb-3 text-white">{{ content('footer.menu_heading') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach($menuLinks as $link)
                            <li><a href="{{ $link['url'] }}" class="hover:text-accent">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Categories (from the catalog) --}}
            @if($footerCategories->isNotEmpty())
                <div>
                    <h3 class="font-semibold text-sm uppercase tracking-wide mb-3 text-white">{{ content('footer.categories_heading') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach($footerCategories as $category)
                            <li><a href="{{ route('category.show', $category->slug) }}" class="hover:text-accent">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Content pages --}}
            @if($footerPages->isNotEmpty())
                <div>
                    <h3 class="font-semibold text-sm uppercase tracking-wide mb-3 text-white">{{ content('footer.pages_heading') }}</h3>
                    <ul class="space-y-2 text-sm">
                        @foreach($footerPages as $page)
                            <li><a href="{{ route('pages.show', $page->slug) }}" class="hover:text-accent">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Contact (from Store settings) --}}
            <div class="col-span-2 lg:col-span-1">
                <h3 class="font-semibold text-sm uppercase tracking-wide mb-3 text-white">{{ content('footer.contact_heading') }}</h3>
                <ul class="space-y-2 text-sm">
                    @if($settings->contact_phone)
                        <li class="flex gap-2"><x-store-icon name="phone" class="w-4 h-4 mt-0.5 flex-shrink-0 text-accent"/><a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings->contact_phone) }}" class="hover:text-accent">{{ $settings->contact_phone }}</a></li>
                    @endif
                    @if($settings->contact_email)
                        <li class="flex gap-2"><x-store-icon name="mail" class="w-4 h-4 mt-0.5 flex-shrink-0 text-accent"/><a href="mailto:{{ $settings->contact_email }}" class="hover:text-accent break-all">{{ $settings->contact_email }}</a></li>
                    @endif
                    @if($settings->store_address)
                        <li class="flex gap-2"><x-store-icon name="location" class="w-4 h-4 mt-0.5 flex-shrink-0 text-accent"/><span>{!! nl2br(e($settings->store_address)) !!}</span></li>
                    @endif
                    @if($settings->opening_hours)
                        <li class="flex gap-2"><x-store-icon name="clock" class="w-4 h-4 mt-0.5 flex-shrink-0 text-accent"/><span>{{ $settings->opening_hours }}</span></li>
                    @endif
                </ul>

                @if($socials->isNotEmpty())
                    <div class="flex items-center gap-3 mt-4">
                        @foreach($socials as $icon => [$label, $url])
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}"
                               class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center hover:bg-accent hover:text-on-accent">
                                <x-store-icon :name="$icon" class="w-4 h-4"/>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-xs text-gray-400 flex flex-col sm:flex-row justify-between gap-2">
                <span>{{ Content::text('footer.copyright') }}</span>
                @if(content('footer.bottom_text'))
                    <span>{{ Content::text('footer.bottom_text') }}</span>
                @endif
            </div>
        </div>
    </footer>
</div>
@stack('scripts')
</body>
</html>
