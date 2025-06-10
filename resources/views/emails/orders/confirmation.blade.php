<x-mail::message>
# ¡Gracias por tu compra!

Hola {{ $order->user ? $order->user->name : $order->shippingAddress->first_name . ' ' . $order->shippingAddress->last_name }},

Tu pedido **#{{ $order->order_number }}** ha sido confirmado y se encuentra en proceso.

## Resumen del Pedido

**Fecha:** {{ $order->created_at->format('d/m/Y H:i') }}
**Estado:** {{ ucfirst($order->status) }}
**Estado del Pago:** {{ ucfirst($order->payment_status) }}

## Productos Ordenados

@foreach($order->orderItems as $item)
**{{ $item->product_name }}**
Cantidad: {{ $item->quantity }}
Precio unitario: ${{ number_format($item->price_per_unit, 2) }} MXN
Subtotal: ${{ number_format($item->total_price, 2) }} MXN

---
@endforeach

## Información de Envío

**{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}**
@if($order->shippingAddress->company)
{{ $order->shippingAddress->company }}
@endif
{{ $order->shippingAddress->address_line_1 }}
@if($order->shippingAddress->address_line_2)
{{ $order->shippingAddress->address_line_2 }}
@endif
{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->state }}
{{ $order->shippingAddress->postal_code }}, {{ $order->shippingAddress->country }}
@if($order->shippingAddress->phone)
Tel: {{ $order->shippingAddress->phone }}
@endif

## Resumen de Costos

**Subtotal:** ${{ number_format($order->subtotal_amount, 2) }} MXN
**Envío:** ${{ number_format($order->shipping_cost, 2) }} MXN
**Total:** ${{ number_format($order->total_amount, 2) }} MXN

<x-mail::button :url="$orderUrl">
Ver Detalles del Pedido
</x-mail::button>

Si tienes alguna pregunta sobre tu pedido, no dudes en contactarnos.

Gracias por elegir {{ config('app.name') }},<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
