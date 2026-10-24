<x-mail::message>
# Thank you, {{ $order->customer_name }}!

We have received your order request **{{ $order->reference }}**. Our team will call or WhatsApp you on **{{ $order->displayPhone() }}** shortly to confirm it.

Payment is **Cash on Delivery** – you don't need to pay anything now.

<x-mail::table>
| Product | Qty | Total |
|:--------|:---:|------:|
@foreach($order->items as $item)
| {{ str_replace('|', '/', $item->product_name) }} | {{ $item->quantity }} | Rs {{ number_format((float) $item->line_total) }} |
@endforeach
| **Total** | **{{ $order->items_count }}** | **Rs {{ number_format((float) $order->subtotal) }}** |
</x-mail::table>

<x-mail::button :url="route('orders.show', $order)">
Track your order
</x-mail::button>

Keep this email – the link above always shows the latest status of your order.

Thanks,<br>
{{ \App\Models\SiteSetting::storeName() }}
</x-mail::message>
