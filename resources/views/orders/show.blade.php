@extends('layouts.frontend')

@section('title', 'Order ' . $order->reference . \App\Support\Content::text('seo.title_suffix'))

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
@endpush

@php
    use App\Enums\OrderStatus;

    $storeWhatsApp = \App\Models\SiteSetting::current()->whatsappLink('Assalam o Alaikum, I have placed order request ' . $order->reference . ' on your website.');
    $isCancelled   = $order->status === OrderStatus::Cancelled;
@endphp

@if(session('order_placed'))
    @push('scripts')
        <script>
            window.ueTrack('lead', {
                kind: 'order_request',
                value: @js((float) $order->subtotal),
                items: @js($order->items->map(fn ($i) => ['id' => $i->product_id, 'name' => $i->product_name, 'price' => (float) $i->unit_price, 'quantity' => $i->quantity])->values()),
            });
        </script>
    @endpush
@endif

@section('content')
    <section class="bg-brand text-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs text-gray-400">Order request</p>
                <h1 class="text-2xl font-bold">{{ $order->reference }}</h1>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $order->status->badgeClasses() }}">
                {{ $order->status->label() }}
            </span>
        </div>
    </section>

    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if(session('success'))
            <div class="text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-3 py-2">
                {{ session('success') }}
            </div>
        @endif

        {{-- Thank-you block right after submitting --}}
        @if(session('order_placed'))
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-5 sm:p-6">
                <h2 class="text-lg font-bold text-emerald-800">{{ \App\Support\Content::text('pages.thankyou_title', ['name' => $order->customer_name]) }}</h2>
                <p class="mt-1 text-sm text-emerald-900">
                    {{ \App\Support\Content::text('pages.thankyou_text', ['name' => $order->customer_name, 'reference' => $order->reference, 'phone' => $order->maskedPhone()]) }}
                    @if($order->customer_email)
                        A confirmation has also been sent to your email.
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if($storeWhatsApp)
                        <a href="{{ $storeWhatsApp }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            {{ \App\Support\Content::text('pages.thankyou_whatsapp_button') }}
                        </a>
                    @endif
                    <a href="{{ route('shop.index') }}"
                       class="inline-flex items-center px-4 py-2 rounded-md border border-emerald-700 text-emerald-800 text-sm font-semibold hover:bg-emerald-100">
                        Continue shopping
                    </a>
                </div>
                <p class="mt-4 text-xs text-emerald-900/80">
                    🔖 Bookmark this page – it is your private link to check this order's status.
                    It is also saved under <a href="{{ route('orders.index') }}" class="underline">My Orders</a> in this browser.
                </p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Status --}}
            <div class="lg:col-span-2 bg-white border border-gray-100 rounded-lg p-5">
                <h2 class="text-sm font-semibold text-slate-900 mb-1">Order status</h2>
                <p class="text-sm text-gray-700 mb-5">{{ $order->status->customerMessage() }}</p>

                @if($isCancelled)
                    <div class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-md px-3 py-2">
                        Cancelled on {{ $order->cancelled_at?->format('d M Y, h:i A') ?? '—' }}
                    </div>
                @else
                    <ol class="relative border-s border-gray-200 ms-2">
                        @foreach(OrderStatus::timeline() as $step)
                            @php $reachedAt = $order->reachedAt($step); @endphp
                            <li class="mb-5 ms-5 last:mb-0">
                                <span class="absolute -start-2 flex items-center justify-center w-4 h-4 rounded-full ring-4 ring-white
                                    {{ $reachedAt ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>
                                <p class="text-sm font-medium {{ $reachedAt ? 'text-slate-900' : 'text-gray-400' }}">
                                    {{ $step->label() }}
                                </p>
                                @if($reachedAt)
                                    <p class="text-xs text-gray-500">{{ $reachedAt->format('d M Y, h:i A') }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if($order->status === OrderStatus::Contacted)
                    <div class="mt-5 text-sm text-sky-900 bg-sky-50 border border-sky-200 rounded-md px-3 py-2">
                        📲 Please check your <strong>WhatsApp</strong>, missed calls
                        @if($order->customer_email) and <strong>email</strong> @endif
                        – we're waiting for your confirmation.
                    </div>
                @endif
            </div>

            {{-- Details --}}
            <div class="bg-white border border-gray-100 rounded-lg p-5 text-sm space-y-2 h-fit">
                <h2 class="text-sm font-semibold text-slate-900 mb-2">Details</h2>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Placed</span><span>{{ $order->created_at->format('d M Y, h:i A') }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Name</span><span class="text-right">{{ $order->customer_name }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Mobile</span><span>{{ $order->maskedPhone() }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-gray-500">Payment</span><span>Cash on Delivery</span></div>

                @if($storeWhatsApp && ! session('order_placed'))
                    <a href="{{ $storeWhatsApp }}" target="_blank" rel="noopener noreferrer"
                       class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                        💬 Ask about this order
                    </a>
                @endif
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-white border border-gray-100 rounded-lg p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <h2 class="text-sm font-semibold text-slate-900">Items ({{ $order->items_count }})</h2>
                <form action="{{ route('orders.reorder', $order) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="text-xs px-3 py-1.5 rounded-md border border-brand text-brand hover:bg-brand hover:text-white">
                        🔁 Order these again
                    </button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                        <th class="py-2 pr-3 font-medium">Product</th>
                        <th class="py-2 px-3 font-medium text-center">Qty</th>
                        <th class="py-2 px-3 font-medium text-right whitespace-nowrap">Unit price</th>
                        <th class="py-2 pl-3 font-medium text-right">Total</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-2 pr-3">
                                @if($item->product_slug && $item->product_id)
                                    <a href="{{ route('product.show', $item->product_slug) }}" class="hover:text-accent-ink">{{ $item->product_name }}</a>
                                @else
                                    {{ $item->product_name }}
                                @endif
                            </td>
                            <td class="py-2 px-3 text-center">{{ $item->quantity }}</td>
                            <td class="py-2 px-3 text-right whitespace-nowrap">{{ rupees((float) $item->unit_price) }}</td>
                            <td class="py-2 pl-3 text-right whitespace-nowrap font-medium">{{ rupees((float) $item->line_total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="border-t border-gray-200 font-semibold">
                        <td class="pt-3" colspan="3">Total (cash on delivery)</td>
                        <td class="pt-3 text-right whitespace-nowrap">{{ rupees((float) $order->subtotal) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <p class="mt-2 text-[11px] text-gray-500">Final price and any delivery charges are confirmed with you on call.</p>
        </div>
    </section>
@endsection
