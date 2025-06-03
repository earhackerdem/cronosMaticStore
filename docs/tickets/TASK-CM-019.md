**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-019`
* **Título:** Backend - Integración con Pasarela de Pago (PayPal Sandbox)
* **Historia(s) de Usuario Relacionada(s):** HU3.8
* **Descripción:**
    1.  Configurar SDK de PayPal o la integración API directa.
    2.  Implementar lógica en `PaymentService` o dentro de `OrderService` para:
        * Procesar un pago con los detalles/nonce proporcionados por el frontend.
        * Manejar respuestas de PayPal (éxito, error).
        * Actualizar el estado del pedido y del pago según la respuesta de PayPal.
    3.  (Opcional MVP, pero recomendado) Configurar webhooks de PayPal para confirmaciones de pago asíncronas.
* **Criterios de Aceptación:**
    * Se puede iniciar un proceso de pago con PayPal Sandbox.
    * Se puede simular un pago exitoso y uno fallido.
    * El estado del pedido se actualiza correctamente según el resultado del pago.
* **Prioridad:** Muy Alta 
