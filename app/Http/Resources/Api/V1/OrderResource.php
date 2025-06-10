<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'subtotal_amount' => number_format($this->subtotal_amount, 2, '.', ''),
            'shipping_cost' => number_format($this->shipping_cost, 2, '.', ''),
            'total_amount' => number_format($this->total_amount, 2, '.', ''),
            'payment_gateway' => $this->payment_gateway,
            'payment_id' => $this->payment_id,
            'shipping_method_name' => $this->shipping_method_name,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                ];
            }),

            'guest_email' => $this->when(!$this->user_id, $this->guest_email),

            'shipping_address' => $this->whenLoaded('shippingAddress', function () {
                return new AddressResource($this->shippingAddress);
            }),

            'billing_address' => $this->whenLoaded('billingAddress', function () {
                return $this->billingAddress ? new AddressResource($this->billingAddress) : null;
            }),

            'order_items' => $this->whenLoaded('orderItems', function () {
                return OrderItemResource::collection($this->orderItems);
            }),

            // Computed attributes
            'can_be_cancelled' => $this->when(method_exists($this, 'canBeCancelled'), $this->canBeCancelled()),
            'status_label' => $this->getStatusLabel(),
            'payment_status_label' => $this->getPaymentStatusLabel(),
        ];
    }
}
