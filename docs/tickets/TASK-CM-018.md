**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-018`
* **Título:** Backend - Modelo, Migración y Lógica Base para Pedidos (`orders`, `order_items`)
* **Historia(s) de Usuario Relacionada(s):** HU3.1 a HU3.11, HU5.1, HU5.2
* **Descripción:**
    1.  Crear migraciones para `orders` y `order_items` según modelo de datos.
    2.  Crear modelos Eloquent `App\Models\Order` y `App\Models\OrderItem` con sus relaciones.
    3.  Implementar lógica en un `OrderService` para:
        * Crear un pedido a partir de un carrito (transferir ítems, calcular totales).
        * Validar datos del checkout (direcciones, etc.).
        * Actualizar stock de productos tras la creación del pedido.
        * Generar número de pedido único.
* **Criterios de Aceptación:**
    * Tablas `orders` y `order_items` creadas.
    * Modelos funcionales.
    * `OrderService` puede crear un pedido válido desde un carrito.
* **Dependencias:** `TASK-CM-013` (Lógica de Carrito)
* **Prioridad:** Muy Alta 
