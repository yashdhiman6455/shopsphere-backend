<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #059669; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .body { padding: 30px; color: #374151; line-height: 1.6; }
        .body h2 { color: #1a1a2e; margin-top: 0; }
        .order-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .order-box p { margin: 5px 0; }
        .order-box .label { font-weight: 700; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f3f4f6; text-align: left; padding: 10px; font-size: 13px; color: #6b7280; text-transform: uppercase; }
        td { padding: 10px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .total-row td { font-weight: 700; border-top: 2px solid #e5e7eb; font-size: 16px; }
        .footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmed!</h1>
        </div>
        <div class="body">
            <h2>Hi {{ $order->shipping_name }},</h2>
            <p>Thank you for your order! We've received your order and it's being processed.</p>

            <div class="order-box">
                <p><span class="label">Order Number:</span> #{{ $order->order_number }}</p>
                <p><span class="label">Date:</span> {{ $order->created_at->format('M d, Y') }}</p>
                <p><span class="label">Payment Method:</span> {{ ucfirst($order->payment_method) }}</p>
                <p><span class="label">Shipping Address:</span> {{ $order->shipping_address }}, {{ $order->shipping_city }}{{ $order->shipping_state ? ', ' . $order->shipping_state : '' }} {{ $order->shipping_zip_code }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th style="text-align:right">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Product' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td style="text-align:right">${{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Subtotal</td>
                        <td style="text-align:right">${{ number_format($order->subtotal, 2) }}</td>
                    </tr>
                    @if($order->discount > 0)
                    <tr>
                        <td colspan="2">Discount</td>
                        <td style="text-align:right; color:#059669;">-${{ number_format($order->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td colspan="2">Tax</td>
                        <td style="text-align:right">${{ number_format($order->tax, 2) }}</td>
                    </tr>
                    @if($order->shipping_cost > 0)
                    <tr>
                        <td colspan="2">Shipping</td>
                        <td style="text-align:right">${{ number_format($order->shipping_cost, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td style="text-align:right">${{ number_format($order->total, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <p>You can track your order status in your account dashboard.</p>
            <p>Thank you for shopping with us!</p>
            <p><strong>The ShopSphere Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} ShopSphere. All rights reserved.
        </div>
    </div>
</body>
</html>
