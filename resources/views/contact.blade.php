@extends('layouts.frontend')

@section('title', \App\Support\Content::text('pages.contact_title') . \App\Support\Content::text('seo.title_suffix'))

@section('content')
    {{-- Hero / heading --}}
    <section class="bg-brand text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold mb-2">{{ \App\Support\Content::text('pages.contact_title') }}</h1>
            <p class="text-sm text-gray-300">
                {{ \App\Support\Content::text('pages.contact_intro') }}
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
                        <div class="w-9 h-9 flex-shrink-0 rounded-full bg-brand text-accent flex items-center justify-center">
                            <x-store-icon name="phone" class="w-4 h-4"/>
                        </div>
                        <div>
                            <div class="font-semibold">
                                @if($settings->contact_phone)
                                    <a href="tel:{{ $settings->contact_phone }}" class="hover:text-accent-ink">
                                        {{ $settings->contact_phone }}
                                    </a>
                                @else
                                    Not set yet
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $settings->opening_hours ?: \App\Support\Content::text('pages.contact_phone_note') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">Email</h2>
                    <div class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-9 h-9 flex-shrink-0 rounded-full bg-brand text-accent flex items-center justify-center">
                            <x-store-icon name="mail" class="w-4 h-4"/>
                        </div>
                        <div>
                            <div class="font-semibold">
                                @if($settings->contact_email)
                                    <a href="mailto:{{ $settings->contact_email }}" class="hover:text-accent-ink">
                                        {{ $settings->contact_email }}
                                    </a>
                                @else
                                    Not set yet
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ \App\Support\Content::text('pages.contact_email_note') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-700 mb-3">Store Location</h2>
                    <div class="flex items-start gap-3 text-sm text-gray-700">
                        <div class="w-9 h-9 flex-shrink-0 rounded-full bg-brand text-accent flex items-center justify-center">
                            <x-store-icon name="location" class="w-4 h-4"/>
                        </div>
                        <div>
                            <div class="font-semibold mb-1">
                                {{ $settings->store_name }}
                            </div>
                            <div class="text-xs text-gray-600 whitespace-pre-line">{{ $settings->store_address }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: contact form --}}
            <div class="lg:col-span-2">
                <div class="bg-white border border-gray-100 rounded-lg shadow-sm p-5 sm:p-6">
                    <h2 class="text-lg font-semibold mb-4">Send us a message</h2>

                    @if($errors->any() && ! $errors->has('form'))
                    <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2" role="alert">
                        Please check the highlighted {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} below.
                    </div>
                    @endif
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

                    <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4" data-disable-on-submit>
                        @csrf

                        {{-- Honeypot field --}}
                        <div style="display:none;" aria-hidden="true">
                            <label>Website
                                <input type="text" name="website" tabindex="-1" autocomplete="off">
                            </label>
                        </div>

                        <div>
                            <label for="contact-name" class="block text-xs font-semibold text-gray-700 mb-1">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input id="contact-name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"
                                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('name') !border-red-500 bg-red-50/40 @enderror">
                            @error('name')
                            <p id="name-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-phone" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Phone / WhatsApp
                                </label>
                                <input id="contact-phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="50" inputmode="tel" autocomplete="tel"
                                       placeholder="0300 1234567"
                                       @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('phone') !border-red-500 bg-red-50/40 @enderror">
                                @error('phone')
                                <p id="phone-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="contact-email" class="block text-xs font-semibold text-gray-700 mb-1">
                                    Email
                                </label>
                                <input id="contact-email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email"
                                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('email') !border-red-500 bg-red-50/40 @enderror">
                                @error('email')
                                <p id="email-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="sm:col-span-2 -mt-2 text-[11px] text-gray-500">Give us a phone number or an email (or both) so we can reply.</p>
                        </div>

                        <div>
                            <label for="contact-subject" class="block text-xs font-semibold text-gray-700 mb-1">
                                Subject <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <input id="contact-subject" type="text" name="subject" value="{{ old('subject') }}" maxlength="255"
                                   @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('subject') !border-red-500 bg-red-50/40 @enderror">
                            @error('subject')
                            <p id="subject-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-message" class="block text-xs font-semibold text-gray-700 mb-1">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea id="contact-message" name="message" rows="5" required minlength="10" maxlength="5000"
                                      @error('message') aria-invalid="true" aria-describedby="message-error" @enderror
                                      class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-accent @error('message') !border-red-500 bg-red-50/40 @enderror">{{ old('message') }}</textarea>
                            @error('message')
                            <p id="message-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <button type="submit"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90 disabled:opacity-60 disabled:cursor-wait">
                                {{ \App\Support\Content::text('pages.contact_button') }}
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
            @php $mapSrc = \App\Models\SiteSetting::extractMapSrc($settings->map_embed_url); @endphp
            @if($mapSrc)
                <div class="w-full h-64 md:h-80 rounded-lg overflow-hidden border border-gray-200">
                    <iframe
                        src="{{ $mapSrc }}"
                        title="Store location map"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                    ></iframe>
                </div>
            @elseif($settings->map_embed_url && str_starts_with($settings->map_embed_url, 'https://'))
                <a href="{{ $settings->map_embed_url }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">
                    <x-store-icon name="location" class="w-4 h-4"/> Open our location in Google Maps
                </a>
            @endif

            @php
                $contactSocials = collect([
                    'facebook'  => ['Facebook', $settings->facebook_url],
                    'instagram' => ['Instagram', $settings->instagram_url],
                    'youtube'   => ['YouTube', $settings->youtube_url],
                    'x'         => ['X (Twitter)', $settings->x_url],
                    'whatsapp'  => ['WhatsApp', $settings->whatsappLink()],
                ])->filter(fn ($s) => $s[1] && preg_match('/^https?:\/\//i', $s[1]));
            @endphp
            @if($contactSocials->isNotEmpty())
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-sm font-semibold text-gray-700">Follow Us:</span>
                    @foreach($contactSocials as $icon => [$label, $url])
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $label }}"
                           class="w-9 h-9 rounded-full bg-brand text-white flex items-center justify-center hover:bg-accent hover:text-on-accent">
                            <x-store-icon :name="$icon" class="w-4 h-4"/>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
