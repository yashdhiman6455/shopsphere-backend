<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'image' => $this->image,
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('image')),
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
