@extends('layouts.frontend')

@section('title', 'Contact Us – ' . config('app.name'))

@section('content')
    {{-- Hero / heading --}}
    <section class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold mb-2">Contact Us</h1>
            <p class="text-sm text-gray-300">
                Get in touch with Usman Electronics for any queries, quotes, or support.
            </p>
        </div>
    </section>

    {{-- Contact info + form --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left: contact info --}}
            <div class="space-y-6">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">Call Us</h2>
                    <div class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">
                            📞
                        </div>
                        <div>
                            <div class="font-semibold">
                                @if($settings->contact_phone)
                                    <a href="tel:{{ $settings->contact_phone }}" class="hover:text-amber-600">
                                        {{ $settings->contact_phone }}
                                    </a>
                                @else
                                    Not set yet
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Available during business hours.
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">Email</h2>
                    <div class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">
                            ✉️
                        </div>
                        <div>
                            <div class="font-semibold">
                                @if($settings->contact_email)
                                    <a href="mailto:{{ $settings->contact_email }}" class="hover:text-amber-600">
                                        {{ $settings->contact_email }}
                                    </a>
                                @else
                                    Not set yet
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                We usually respond within 1–2 business days.
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">Store Location</h2>
                    <div class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-9 h-9 rounded-full bg-slate-900 text-amber-400 flex items-center justify-center text-lg">
                            📍
                        </div>
                        <div>
                            <div class="font-semibold mb-1">
                                Usman Electronics
                            </div>
                            <div class="text-xs text-gray-600 whitespace-pre-line">
                                {{ $settings->store_address ?? "Al-Hamra Electric Store\nMadina Electric Market, 37\nShah Alam Gate, Lahore, Pakistan" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: contact form --}}
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-100 rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Send us a message</h2>

                    @if($errors->has('form'))
                        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                            {{ $errors->first('form') }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-3 py-2">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
                        @csrf

                        {{-- Honeypot field --}}
                        <div style="display:none;">
                            <label>Website
                                <input type="text" name="website" autocomplete="off">
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                                >
                                @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                                >
                                @error('email')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Phone
                                </label>
                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                                >
                                @error('phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Subject
                                </label>
                                <input
                                    type="text"
                                    name="subject"
                                    value="{{ old('subject') }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                                >
                                @error('subject')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                name="message"
                                rows="5"
                                required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"
                            >{{ old('message') }}</textarea>
                            @error('message')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-5 py-2.5 rounded-md bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800"
                            >
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Map + social icons --}}
    <section class="bg-white border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="w-full h-64 md:h-80 rounded-lg overflow-hidden border border-gray-200">
                <iframe
                    src="{{ $settings->map_embed_url ?? 'https://maps.app.goo.gl/HW8Fk18AqHee17ht8' }}"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <span class="text-sm font-semibold text-gray-700">Follow Us:</span>

                @if($settings->facebook_url)
                    <a href="{{ $settings->facebook_url }}" target="_blank"
                       class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center hover:bg-amber-400 hover:text-slate-900 text-lg">
                        f
                    </a>
                @endif

                @if($settings->instagram_url)
                    <a href="{{ $settings->instagram_url }}" target="_blank"
                       class="w-9 h-9 rounded-full bg-slate-900 text-white flex items-center justify-center hover:bg-amber-400 hover:text-slate-900 text-lg">
                        📸
                    </a>
                @endif

                @if($settings->whatsapp_url)
                    <a href="{{ $settings->whatsapp_url }}" target="_blank"
                       class="w-9 h-9 rounded-full bg-emerald-500 text-white flex items-center justify-center hover:bg-emerald-400 text-lg">
                        🟢
                    </a>
                @endif
            </div>
        </div>
    </section>
@endsection
