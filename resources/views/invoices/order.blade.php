<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #374151; font-size: 13px; }
        .invoice-box { max-width: 800px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #4f46e5; padding-bottom: 20px; margin-bottom: 20px; }
        .brand h1 { color: #4f46e5; margin: 0; font-size: 28px; }
        .brand p { color: #6b7280; margin: 5px 0 0; font-size: 12px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { color: #1a1a2e; margin: 0; font-size: 20px; text-transform: uppercase; }
        .invoice-meta p { margin: 3px 0; color: #6b7280; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .info-block { width: 48%; }
        .info-block h3 { font-size: 11px; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px; margin: 0 0 8px; }
        .info-block p { margin: 2px 0; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        th { background: #f3f4f6; text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.5px; }
        td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .totals { width: 300px; margin-left: auto; }
        .totals table td { padding: 6px 12px; }
        .totals .total-row td { font-size: 16px; font-weight: 700; border-top: 2px solid #4f46e5; color: #1a1a2e; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 11px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-processing { background: #dbeafe; color: #2563eb; }
        .status-shipped { background: #e0e7ff; color: #4f46e5; }
        .status-delivered { background: #ecfdf5; color: #059669; }
        .status-cancelled { background: #fef2f2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="brand">
                <h1>ShopSphere</h1>
                <p>Your one-stop e-commerce destination</p>
            </div>
            <div class="invoice-meta">
                <h2>Invoice</h2>
                <p><strong>#{{ $order->order_number }}</strong></p>
                <p>Date: {{ $order->created_at->format('M d, Y') }}</p>
                <p>Status: <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-block">
                <h3>Bill To</h3>
                <p><strong>{{ $order->shipping_name }}</strong></p>
                <p>{{ $order->shipping_email }}</p>
                <p>{{ $order->shipping_address }}</p>
                <p>{{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_zip_code }}</p>
                @if($order->shipping_phone)
                <p>{{ $order->shipping_phone }}</p>
                @endif
            </div>
            <div class="info-block">
                <h3>Payment Details</h3>
                <p><strong>Method:</strong> {{ $order->payment_method === 'cod' ? 'Cash on Delivery' : ucfirst($order->payment_method) }}</p>
                <p><strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}</p>
                @if($order->coupon)
                <p><strong>Coupon:</strong> {{ $order->coupon->code }}</p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:45%">Item</th>
                    <th class="text-right" style="width:15%">Unit Price</th>
                    <th class="text-right" style="width:10%">Qty</th>
                    <th class="text-right" style="width:25%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->name ?? 'Product' }}</td>
                    <td class="text-right">${{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount > 0)
                <tr>
                    <td>Discount</td>
                    <td class="text-right" style="color:#059669;">-${{ number_format($order->discount, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Tax (8%)</td>
                    <td class="text-right">${{ number_format($order->tax, 2) }}</td>
                </tr>
                @if($order->shipping_cost > 0)
                <tr>
                    <td>Shipping</td>
                    <td class="text-right">${{ number_format($order->shipping_cost, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right">${{ number_format($order->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>ShopSphere &copy; {{ date('Y') }} | This is a computer-generated invoice.</p>
        </div>
    </div>
</body>
</html>
