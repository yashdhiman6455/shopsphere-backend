<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function download(int $id, Request $request): Response
    {
        $order = Order::with(['items.product', 'coupon', 'user'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $pdf = Pdf::loadView('invoices.order', ['order' => $order])
            ->setPaper('a4');

        return $pdf->download("invoice-{$order->order_number}.pdf");
    }
}
