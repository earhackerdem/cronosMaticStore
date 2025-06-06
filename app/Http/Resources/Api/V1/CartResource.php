<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user_id' => $this->user_id,
            'session_id' => $this->when($this->user_id === null, $this->session_id),
            'total_items' => $this->total_items,
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->isExpired(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'summary' => [
                'items_count' => $this->items->count() ?? 0,
                'unique_products' => $this->items->count() ?? 0,
                'total_quantity' => $this->total_items,
                'subtotal' => number_format((float) $this->total_amount, 2, '.', ''),
                'tax' => '0.00', // Placeholder para futuras implementaciones de impuestos
                'total' => number_format((float) $this->total_amount, 2, '.', ''),
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
