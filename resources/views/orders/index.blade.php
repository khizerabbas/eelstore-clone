@extends('layouts.frontend')

@section('title', \App\Support\Content::text('pages.my_orders_title') . \App\Support\Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="bg-brand text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">{{ \App\Support\Content::text('pages.my_orders_title') }}</h1>
            <p class="mt-1 text-sm text-gray-300">{{ \App\Support\Content::text('pages.my_orders_intro') }}</p>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($orders->isEmpty())
            <div class="bg-white border border-gray-100 rounded-lg p-6 text-sm text-gray-600">
                <p>{{ \App\Support\Content::text('pages.my_orders_empty') }}</p>
                <p class="mt-2 text-xs text-gray-500">
                    Placed an order on another device or cleared your browser data? Open the link from your confirmation email,
                    or simply call / WhatsApp us with your mobile number – we have your order on record.
                </p>
                <a href="{{ route('shop.index') }}"
                   class="mt-4 inline-flex items-center justify-center px-4 py-2 rounded-md bg-brand text-white text-sm font-semibold hover:bg-brand/90">
                    Start shopping
                </a>
            </div>
        @else
            <ul class="space-y-3">
                @foreach($orders as $order)
                    <li>
                        <a href="{{ route('orders.show', $order) }}"
                           class="block bg-white border border-gray-100 rounded-lg p-4 hover:border-accent/60 hover:shadow-sm transition">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $order->reference }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $order->created_at->format('d M Y, h:i A') }}
                                        · {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold">Rs {{ number_format((float) $order->subtotal) }}</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $order->status->badgeClasses() }}">
                                        {{ $order->status->label() }}
                                    </span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600">{{ $order->status->customerMessage() }}</p>
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="mt-6 text-xs text-gray-500">
                {{ \App\Support\Content::text('pages.my_orders_note') }}
            </p>
        @endif
    </section>
@endsection
