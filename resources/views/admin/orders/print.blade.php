<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow">
    <title>Packing slip {{ $order->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 24px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0; }
        .muted { color: #555; }
        .row { display: flex; justify-content: space-between; gap: 24px; margin-bottom: 18px; }
        .box { border: 1px solid #ccc; border-radius: 6px; padding: 10px 12px; flex: 1; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #ddd; padding: 6px 4px; text-align: left; }
        th.num, td.num { text-align: right; }
        tfoot td { font-weight: bold; border-bottom: none; padding-top: 10px; }
        .cod { margin-top: 18px; padding: 10px; border: 2px dashed #111; text-align: center; font-size: 16px; font-weight: bold; }
        .print-btn { margin-bottom: 16px; }
        @media print { .print-btn { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Print</button>

<div class="row">
    <div>
        <h1>{{ $settings->store_name ?? config('app.name') }}</h1>
        <div class="muted">{!! nl2br(e($settings->store_address)) !!}</div>
        <div class="muted">{{ $settings->contact_phone }}</div>
    </div>
    <div style="text-align:right">
        <h1>Packing slip</h1>
        <div><strong>{{ $order->reference }}</strong></div>
        <div class="muted">Requested {{ $order->created_at->format('d M Y, h:i A') }}</div>
        <div class="muted">Status: {{ $order->status->getLabel() }}</div>
    </div>
</div>

<div class="row">
    <div class="box">
        <strong>Deliver to</strong><br>
        {{ $order->customer_name }}<br>
        {{ $order->displayPhone() }}<br>
        @if($order->customer_address){!! nl2br(e($order->customer_address)) !!}<br>@endif
        @if($order->customer_email){{ $order->customer_email }}@endif
    </div>
    @if($order->customer_comment)
        <div class="box">
            <strong>Customer comment</strong><br>
            {!! nl2br(e($order->customer_comment)) !!}
        </div>
    @endif
</div>

<table>
    <thead>
    <tr>
        <th>#</th>
        <th>Product</th>
        <th>SKU</th>
        <th class="num">Qty</th>
        <th class="num">Unit price</th>
        <th class="num">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($order->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->product_name }}</td>
            <td>{{ $item->sku ?? '—' }}</td>
            <td class="num">{{ $item->quantity }}</td>
            <td class="num">Rs {{ number_format((float) $item->unit_price) }}</td>
            <td class="num">Rs {{ number_format((float) $item->line_total) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="3">Items: {{ $order->items_count }}</td>
        <td colspan="2" class="num">Total</td>
        <td class="num">Rs {{ number_format((float) $order->subtotal) }}</td>
    </tr>
    </tfoot>
</table>

<div class="cod">CASH ON DELIVERY – collect Rs {{ number_format((float) $order->subtotal) }} (+ delivery charges if agreed)</div>
</body>
</html>
