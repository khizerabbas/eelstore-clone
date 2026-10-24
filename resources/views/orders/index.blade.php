@extends('layouts.frontend')

@section('title', 'My Orders – ' . config('app.name'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    <section class="bg-slate-900 text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">My Orders</h1>
            <p class="mt-1 text-sm text-gray-300">Order requests you have placed from this browser.</p>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($orders->isEmpty())
            <div class="bg-white border border-gray-100 rounded-lg p-6 text-sm text-gray-600">
                <p>No order requests found in this browser.</p>
                <p class="mt-2 text-xs text-gray-500">
                    Placed an order on another device or cleared your browser data? Open the link from your confirmation email,
                    or simply call / WhatsApp us with your mobile number – we have your order on record.
                </p>
                <a href="{{ route('shop.index') }}"
                   class="mt-4 inline-flex items-center justify-center px-4 py-2 rounded-md bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                    Start shopping
                </a>
            </div>
        @else
            <ul class="space-y-3">
                @foreach($orders as $order)
                    <li>
                        <a href="{{ route('orders.show', $order) }}"
                           class="block bg-white border border-gray-100 rounded-lg p-4 hover:border-amber-300 hover:shadow-sm transition">
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
                This list is saved only in this browser. If you clear your cookies it will disappear – your orders are still safe with us,
                just contact us with your mobile number.
            </p>
        @endif
    </section>
@endsection
