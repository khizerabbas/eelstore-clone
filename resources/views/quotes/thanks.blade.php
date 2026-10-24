@extends('layouts.frontend')

@php use App\Support\Content; @endphp

@section('title', Content::text('pages.quote_title') . Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    @php
        $storeWhatsApp = \App\Models\SiteSetting::current()->whatsappLink(
            $reference ? 'Assalam o Alaikum, I sent quote request ' . $reference . ' on your website.' : null
        );
    @endphp

    <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6 text-center">
            <h1 class="text-xl font-bold text-emerald-800">{{ Content::text('pages.quote_thanks_title') }}</h1>
            @if($reference)
                <p class="mt-2 text-sm text-emerald-900">Reference: <strong>{{ $reference }}</strong></p>
            @endif
            <p class="mt-2 text-sm text-emerald-900">{{ Content::text('pages.quote_thanks_text') }}</p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                @if($storeWhatsApp)
                    <a href="{{ $storeWhatsApp }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        {{ Content::text('pages.thankyou_whatsapp_button') }}
                    </a>
                @endif
                <a href="{{ route('shop.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-md border border-emerald-700 text-emerald-800 text-sm font-semibold hover:bg-emerald-100">
                    Continue shopping
                </a>
            </div>
        </div>
    </section>
@endsection
