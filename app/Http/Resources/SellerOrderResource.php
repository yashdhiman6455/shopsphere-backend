<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items', fn () => $this->items);
        $sellerSubtotal = $items instanceof \Illuminate\Support\Collection
            ? (float) $items->sum('total')
            : (float) $this->subtotal;

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $sellerSubtotal,
            'total' => $sellerSubtotal,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'customer' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'product' => $item->relationLoaded('product') && $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'price' => (float) $item->product->price,
                        'image' => $item->product->image,
                    ] : null,
                ];
            })),
            'shipping_address' => $this->shipping_address,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'shipping_zip_code' => $this->shipping_zip_code,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
