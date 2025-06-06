<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_price' => number_format((float) $this->unit_price, 2, '.', ''),
            'total_price' => number_format((float) $this->total_price, 2, '.', ''),
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'description' => $this->product->description,
                'price' => number_format((float) $this->product->price, 2, '.', ''),
                'stock_quantity' => $this->product->stock_quantity,
                'is_active' => $this->product->is_active,
                'image_url' => $this->product->image_url,
                'category' => $this->whenLoaded('product.category', function () {
                    return [
                        'id' => $this->product->category->id,
                        'name' => $this->product->category->name,
                        'slug' => $this->product->category->slug,
                    ];
                }),
            ],
            'subtotal' => number_format((float) $this->total_price, 2, '.', ''),
            'added_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
