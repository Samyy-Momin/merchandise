<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .header { margin-bottom: 16px; }
        .header h1 { margin: 0 0 4px 0; font-size: 20px; }
        .meta { margin-top: 8px; }
        .meta div { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; }
        th { background: #f5f5f5; text-align: left; }
        tfoot td { font-weight: bold; }
    </style>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    </head>
<body>
    <div class="header">
        <h1>Merchandise</h1>
        <div>Invoice #: {{ $invoice->invoice_number }}</div>
        <div>Order ID: {{ $order->id }}</div>
        <div class="meta">
            <div>Date: {{ $invoice->created_at->format('d/m/Y') }}</div>
            <div>Buyer: {{ $buyer_name ?? $order->user_id }}</div>
            @if($address)
                <div>Address: {{ $address->address_line }}, {{ $address->city }}, {{ $address->state }} - {{ $address->pincode }}</div>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Requested</th>
                <th>Approved</th>
                <th>Delivered</th>
                <th>Received</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
            <tr>
                <td>{{ $it['product_name'] }}</td>
                <td style="text-align:right;">{{ $it['requested_qty'] }}</td>
                <td style="text-align:right;">{{ $it['approved_qty'] }}</td>
                <td style="text-align:right;">{{ $it['delivered_qty'] }}</td>
                <td style="text-align:right;">{{ $it['received_qty'] }}</td>
                <td style="text-align:right;">{{ number_format($it['price'], 2) }}</td>
                <td style="text-align:right;">{{ number_format($it['line_total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" style="text-align:right;">Total Amount</td>
                <td style="text-align:right;">{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" style="text-align:right;">Status</td>
                <td style="text-align:right;">{{ strtoupper($invoice->status) }}</td>
            </tr>
            @if($order->acknowledgements->count())
            <tr>
                <td colspan="7">Remarks: {{ optional($order->acknowledgements->sortByDesc('id')->first())->remarks }}</td>
            </tr>
            @endif
        </tfoot>
    </table>
</body>
</html>

