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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            {{-- Logo / brand --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 min-w-0">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-10 w-auto max-w-[180px] object-contain">
                @else
                    <span class="bg-accent text-on-accent w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-full font-extrabold text-lg">
                        {{ content('branding.logo_text') }}
                    </span>
                @endif
                @if(content('branding.show_name'))
                    <span class="leading-tight hidden sm:block">
                        <span class="block font-bold text-lg tracking-wide">{{ $storeName }}</span>
                        @if(content('branding.tagline'))
                            <span class="block text-[11px] text-gray-300">{{ content('branding.tagline') }}</span>
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
                    class="bg-white/10 border border-white/20 text-sm rounded-md pl-3 pr-8 py-2 focus:outline-none focus:ring-2 focus:ring-accent [&>option]:text-gray-900"
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
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            {{-- My orders / wishlist / cart + mobile buttons --}}
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-3 text-xs">
                    @if($showMyOrders)
                        <a href="{{ route('orders.index') }}" class="flex items-center gap-1 hover:text-accent">
                            📦 {{ content('header.nav_my_orders') }}
                        </a>
                    @endif

                    @if($showWishlist)
                        <a href="{{ route('wishlist.index') }}" class="flex items-center gap-1 hover:text-accent">
                            ❤️ {{ content('header.nav_wishlist') }}
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-accent text-on-accent text-xs font-semibold" id="wishlist-count">{{ $wishlistCount }}</span>
                        </a>
                    @endif

                    <a href="{{ route('cart.index') }}" class="flex items-center gap-1 hover:text-accent">
                        🛒 {{ content('header.nav_cart') }}
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-accent text-on-accent text-xs font-semibold" id="cart-count">{{ $cartCount }}</span>
                    </a>
                </div>

                <button id="mobile-search-toggle" type="button" aria-label="Search"
                        class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-md bg-white/10 text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                    </svg>
                </button>

                <button id="mobile-menu-toggle" type="button"
                        class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-md bg-white/10 text-gray-200">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile search panel --}}
        <div id="mobile-search-panel" class="md:hidden hidden border-t border-white/10 px-4 py-3">
            <form action="{{ route('search') }}" method="GET" class="flex gap-2" role="search">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ content('header.search_placeholder') }}"
                       aria-label="{{ content('header.search_placeholder') }}"
                       class="flex-1 bg-white/10 border border-white/20 text-sm rounded-md px-3 py-2 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-accent">
                <button type="submit" class="px-4 py-2 rounded-md bg-accent text-on-accent text-sm font-semibold">Go</button>
            </form>
        </div>

        {{-- Mobile nav panel --}}
        <div id="mobile-menu-panel" class="md:hidden hidden bg-brand text-gray-100 border-t border-white/10">
            <div class="px-4 pt-3 pb-4 space-y-3">
                <a href="{{ route('home') }}" class="block py-1">{{ content('header.nav_home') }}</a>

                <details class="border-t border-white/10 pt-3">
                    <summary class="list-none flex items-center justify-between py-1 cursor-pointer">
                        <span>{{ content('header.nav_categories') }}</span>
                        <span class="text-xs">▾</span>
                    </summary>
                    <div class="mt-2 space-y-1 pl-3 max-h-64 overflow-y-auto text-sm">
                        @foreach($searchCategories as $category)
                            <a href="{{ route('category.show', $category->slug) }}" class="block py-1">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </details>

                <a href="{{ route('shop.index') }}" class="block py-1">{{ content('header.nav_shop') }}</a>
                @foreach($extraLinks as $link)
                    <a href="{{ $link['url'] }}" class="block py-1">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ route('contact.show') }}" class="block py-1">{{ content('header.nav_contact') }}</a>

                <div class="mt-3 flex flex-wrap items-center gap-4 border-t border-white/10 pt-3 text-sm">
                    @if($showMyOrders)
                        <a href="{{ route('orders.index') }}">📦 {{ content('header.nav_my_orders') }}</a>
                    @endif
                    @if($showWishlist)
                        <a href="{{ route('wishlist.index') }}">❤️ {{ content('header.nav_wishlist') }} (<span id="wishlist-count-mobile">{{ $wishlistCount }}</span>)</a>
                    @endif
                    <a href="{{ route('cart.index') }}">🛒 {{ content('header.nav_cart') }} (<span id="cart-count-mobile">{{ $cartCount }}</span>)</a>
                </div>
            </div>
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 sm:grid-cols-2 {{ $footerPages->isNotEmpty() ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-8">
            {{-- About --}}
            <div>
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
            <div>
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
</body>
</html>
