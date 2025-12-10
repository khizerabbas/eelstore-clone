<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<div id="toast"
     class="fixed bottom-4 right-4 z-50 px-4 py-2 rounded-md bg-slate-900 text-white text-xs shadow-lg
            opacity-0 pointer-events-none transition-opacity duration-300 hidden">
    <!-- message injected by JS -->
</div>
<body class="font-sans antialiased bg-gray-100">
@php
    $cart = session('cart', []);
    $cartCount = 0;

    if (is_array($cart)) {
        foreach ($cart as $line) {
            $cartCount += isset($line['quantity']) ? (int) $line['quantity'] : 0;
        }
    }

    $wishlist = session('wishlist', []);
    $wishlistCount = is_array($wishlist) ? count($wishlist) : 0;
@endphp
<div class="min-h-screen flex flex-col">

    {{-- Top contact bar --}}
    <div class="w-full bg-slate-900 text-gray-200 text-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-end items-center h-9">
            <span class="hidden sm:inline-block mr-2">Contact Us:</span>

            @if(!empty($settings?->contact_phone))
                <a href="tel:{{ $settings->contact_phone }}" class="font-medium hover:text-amber-400">
                    {{ $settings->contact_phone }}
                </a>
            @else
                <span class="text-gray-400">Phone not set</span>
            @endif
        </div>
    </div>

    {{-- Main header --}}
    <header class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
            {{-- Logo / brand --}}
            <div class="flex items-center gap-2">
                {{-- Logo / brand --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="bg-amber-400 text-slate-900 w-10 h-10 flex items-center justify-center rounded-full font-extrabold text-xl">
                        UE
                    </div>
                    <div class="leading-tight hidden sm:block">
                        <div class="font-bold text-lg tracking-wide">Usman Electronics</div>
                        <div class="text-xs text-gray-300">The Electric Store</div>
                    </div>
                </a>
            </div>

            {{-- Category + search (desktop) --}}
            <div class="hidden md:flex flex-1 items-center gap-3">
                {{-- Category dropdown --}}
                <div class="relative">
                    <select
                        name="category"
                        form="header-search-form"
                        class="bg-slate-800 border border-slate-700 text-sm rounded-md pl-3 pr-8 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400"
                    >
                        <option value="">All categories</option>
                        @isset($searchCategories)
                            @foreach($searchCategories as $cat)
                                <option
                                    value="{{ $cat->id }}"
                                    {{ (string)request('category') === (string)$cat->id ? 'selected' : '' }}
                                >
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        @endisset
                    </select>
                </div>

                {{-- Search box --}}
                <form id="header-search-form" action="{{ route('search') }}" method="GET" class="flex-1">
                    <div class="relative">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search for products"
                            class="w-full bg-slate-800 border border-slate-700 text-sm rounded-md pl-3 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400"
                        >
                        <button
                            type="submit"
                            class="absolute inset-y-0 right-0 px-3 flex items-center justify-center text-gray-300 hover:text-amber-400"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>


            {{-- Account / wishlist / cart + mobile menu button --}}
            <div class="flex items-center gap-4">
                {{-- Desktop icons --}}
                <div class="hidden sm:flex items-center gap-3 text-xs">
                    <a href="{{ route('wishlist.index') }}" class="flex items-center gap-1">
                        ❤️ Wishlist
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-400 text-slate-900 text-xs font-semibold"
                              id="wishlist-count">
        {{ $wishlistCount }}
    </span>
                    </a>

                    <a href="{{ route('cart.index') }}" class="flex items-center gap-1">
                        🛒 Cart
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-400 text-slate-900 text-xs font-semibold"
                              id="cart-count">
        {{ $cartCount }}
    </span>
                    </a>

                </div>

                {{-- Mobile search icon (opens mobile search panel) --}}
                <button
                    id="mobile-search-toggle"
                    type="button"
                    class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-md bg-slate-800 text-gray-200 mr-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                    </svg>
                </button>

                {{-- Mobile menu toggle --}}
                <button
                    id="mobile-menu-toggle"
                    type="button"
                    class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-md bg-slate-800 text-gray-200"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile search panel --}}
        <div id="mobile-search-panel" class="md:hidden hidden bg-slate-900 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
                <form id="mobile-search-form" action="{{ route('search') }}" method="GET" class="space-y-2">
                    {{-- Category dropdown --}}
                    <select
                        name="category"
                        class="w-full bg-slate-800 border border-slate-700 text-sm rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400"
                    >
                        <option value="">All categories</option>
                        @isset($searchCategories)
                            @foreach($searchCategories as $cat)
                                <option
                                    value="{{ $cat->id }}"
                                    {{ (string)request('category') === (string)$cat->id ? 'selected' : '' }}
                                >
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        @endisset
                    </select>

                    {{-- Search input --}}
                    <div class="flex">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Search for products"
                            class="flex-1 bg-slate-800 border border-slate-700 text-sm rounded-l-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400"
                        >
                        <button
                            type="submit"
                            class="px-3 rounded-r-md border border-l-0 border-slate-700 bg-slate-800 text-gray-200 hover:text-amber-400"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>


        {{-- Secondary nav: shop by categories + main menu --}}
        <div class="bg-slate-800 border-t border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="hidden md:flex items-center justify-between h-11">
                    <div></div> {{-- left side empty for now; can be used later if needed --}}

                    <nav class="flex items-center gap-6 text-sm">
                        <a href="{{ route('home') }}" class="hover:text-amber-400">Home</a>
                        <a href="{{ route('shop.index') }}" class="hover:text-amber-400">Shop</a>
                        <a href="{{ route('contact.show') }}" class="hover:text-amber-400">Contact Us</a>
                    </nav>
                </div>

                {{-- Mobile nav panel --}}
                <div id="mobile-menu-panel" class="md:hidden hidden border-t border-slate-700 pb-3">
                    <nav class="pt-3 flex flex-col gap-2 text-sm">
                        <a href="{{ route('home') }}" class="px-2 py-2 rounded-md hover:bg-slate-700">Home</a>
                        <a href="{{ route('shop.index') }}" class="px-2 py-2 rounded-md hover:bg-slate-700">Shop</a>
                        <a href="{{ route('contact.show') }}" class="px-2 py-2 rounded-md hover:bg-slate-700">Contact Us</a>

                        {{-- Mobile Wishlist / Cart (visible only on small screens) --}}
                        <div class="md:hidden flex items-center gap-4 ml-3">
                            <a href="{{ route('wishlist.index') }}" class="flex items-center gap-1 text-xs text-gray-100">
                                <span>❤️</span>
                                <span>Wishlist</span>
                                <span
                                    id="wishlist-count-mobile"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-400 text-slate-900 text-[11px] font-semibold"
                                >
            {{ $wishlistCount }}
        </span>
                            </a>

                            <a href="{{ route('cart.index') }}" class="flex items-center gap-1 text-xs text-gray-100">
                                <span>🛒</span>
                                <span>Cart</span>
                                <span
                                    id="cart-count-mobile"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-400 text-slate-900 text-[11px] font-semibold"
                                >
            {{ $cartCount }}
        </span>
                            </a>
                        </div>

                    </nav>
                </div>

            </div>
        </div>
    </header>

    {{-- Page content --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-gray-300 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="bg-amber-400 text-slate-900 w-10 h-10 flex items-center justify-center rounded-full font-extrabold text-xl">
                        E
                    </div>
                    <div>
                        <div class="font-bold text-lg tracking-wide">Usman Electronics</div>
                        <div class="text-xs text-gray-400">Solutions for Every Electrical Need</div>
                    </div>
                </div>
                <p class="text-sm text-gray-400">
                    This is a Laravel demo clone of the original eelstore.pk UI. We’ll connect products,
                    categories, cart and checkout step by step.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-sm uppercase tracking-wide mb-3">Menu</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-amber-400">Home</a></li>
                    <li><a href="#" class="hover:text-amber-400">Shop</a></li>
                    <li><a href="{{ route('contact.show') }}" class="hover:text-amber-400">Contact Us</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-sm uppercase tracking-wide mb-3">Category</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('category.show', 'wires') }}" class="hover:text-amber-400">Wires &amp; Cables</a></li>
                    <li><a href="#" class="hover:text-amber-400">Switch &amp; Socket</a></li>
                    <li><a href="{{ route('category.show', 'lighting') }}" class="hover:text-amber-400">Lighting</a></li>
                    <li><a href="#" class="hover:text-amber-400">Accessories</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold text-sm uppercase tracking-wide mb-3">Contact Us</h3>
                <ul class="space-y-2 text-sm">
                    <li>Phone: <a href="tel:+923234146388" class="hover:text-amber-400">+92 323 4146388</a></li>
                    <li>Email: <a href="mailto:info@example.com" class="hover:text-amber-400">info@example.com</a></li>
                    <li>Address line 1<br>Address line 2, Lahore</li>
                </ul>

                <div class="flex items-center gap-3 mt-4">
                    <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-amber-400 hover:text-slate-900 text-lg">f</a>
                    <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-amber-400 hover:text-slate-900 text-lg">▶</a>
                    <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-amber-400 hover:text-slate-900 text-lg">📸</a>
                    <a href="#" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center hover:bg-amber-400 hover:text-slate-900 text-lg">𝕏</a>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-xs text-gray-500 flex flex-col sm:flex-row justify-between gap-2">
                <span>© {{ date('Y') }} Usman Electronics. All rights reserved.</span>
                <span>Built with Laravel &amp; Tailwind CSS.</span>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
