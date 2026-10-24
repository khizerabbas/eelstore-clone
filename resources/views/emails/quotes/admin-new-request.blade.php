<x-mail::message>
# Bulk quote request {{ $quote->reference }}

Someone asked **{{ $storeName }}** for a quote. Please call or WhatsApp them with prices.

**Name:** {{ $quote->name }}<br>
**Phone:** {{ $quote->displayPhone() }}<br>
@if($quote->business_name)
**Business:** {{ $quote->business_name }}<br>
@endif
@if($quote->city)
**City / area:** {{ $quote->city }}<br>
@endif
@if($quote->email)
**Email:** {{ $quote->email }}<br>
@endif
**Received:** {{ $quote->created_at->format('d M Y, h:i A') }}

<x-mail::button :url="$quote->whatsappUrl('Assalam o Alaikum ' . $quote->name . ', this is ' . $storeName . ' about your quote request ' . $quote->reference . '.')" color="success">
WhatsApp {{ $quote->name }}
</x-mail::button>

Or call: [{{ $quote->displayPhone() }}](tel:{{ $quote->phone }})

@if($quote->items)
**Items requested:**

<x-mail::panel>
{!! nl2br(e($quote->items)) !!}
</x-mail::panel>
@endif

@if($quote->cart_items)
**Items from their cart:**

<x-mail::table>
| Product | Qty | Current price |
|:--------|:---:|--------------:|
@foreach($quote->cart_items as $item)
| {{ str_replace('|', '/', $item['product_name']) }} | {{ $item['quantity'] }} | Rs {{ number_format((float) $item['line_total']) }} |
@endforeach
</x-mail::table>
@endif

@if($quote->attachment_path)
📎 The customer attached their list: **{{ $quote->attachment_name }}** (attached to this email).
@endif
</x-mail::message>
