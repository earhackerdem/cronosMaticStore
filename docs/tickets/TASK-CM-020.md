**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-020`
* **Título:** Backend - API Endpoint `POST /orders` para Crear Pedido (Checkout)
* **Historia(s) de Usuario Relacionada(s):** HU3.1 a HU3.8, HU3.11
* **Descripción:**
    1.  Crear `Api/V1/OrderController.php` con un método `store`.
    2.  El método debe:
        * Validar los datos de la petición (direcciones, detalles de pago, etc.).
        * Utilizar `CartService` para obtener el carrito actual.
        * Utilizar `OrderService` para crear el pedido en estado "pendiente_pago".
        * Utilizar `PaymentService` para procesar el pago.
        * Actualizar estado del pedido y stock si el pago es exitoso.
        * Devolver la respuesta apropiada (detalle del pedido creado o error).
    3.  Definir la ruta `POST /api/v1/orders` protegida (Sanctum o manejo de invitado).
* **Criterios de Aceptación:**
    * Se puede crear un pedido exitosamente a través de la API si todos los datos son válidos y el pago simulado es exitoso.
    * Las validaciones de datos funcionan.
    * El stock de los productos se reduce tras una compra exitosa.
    * Se manejan errores de la pasarela de pago.
* **Dependencias:** `TASK-CM-013`, `TASK-CM-018`, `TASK-CM-019`
* **Prioridad:** Muy Alta 
