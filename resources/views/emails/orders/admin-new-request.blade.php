<x-mail::message>
# New order request {{ $order->reference }}

A customer has requested an order on **{{ config('app.name') }}**. Please call or WhatsApp them to confirm it (Cash on Delivery).

**Name:** {{ $order->customer_name }}<br>
**Phone:** {{ $order->displayPhone() }}<br>
@if($order->customer_email)
**Email:** {{ $order->customer_email }}<br>
@endif
@if($order->customer_address)
**Address:** {{ $order->customer_address }}<br>
@endif
**Submitted:** {{ $order->created_at->timezone(config('app.timezone'))->format('d M Y, h:i A') }}

<x-mail::button :url="$order->whatsappUrl('Assalam o Alaikum ' . $order->customer_name . ', this is ' . config('app.name') . ' about your order request ' . $order->reference . '.')" color="success">
WhatsApp {{ $order->customer_name }}
</x-mail::button>

Or call: [{{ $order->displayPhone() }}](tel:{{ $order->customer_phone }})

<x-mail::table>
| Product | Qty | Unit price | Total |
|:--------|:---:|-----------:|------:|
@foreach($order->items as $item)
| {{ str_replace('|', '/', $item->product_name) }}@if($item->sku) ({{ str_replace('|', '/', $item->sku) }})@endif | {{ $item->quantity }} | Rs {{ number_format((float) $item->unit_price) }} | Rs {{ number_format((float) $item->line_total) }} |
@endforeach
| **Total** | **{{ $order->items_count }}** | | **Rs {{ number_format((float) $order->subtotal) }}** |
</x-mail::table>

@if($order->customer_comment)
**Customer comment:**

{{ $order->customer_comment }}
@endif

<x-mail::subcopy>
IP: {{ $order->ip_address ?? 'N/A' }}
</x-mail::subcopy>
</x-mail::message>
