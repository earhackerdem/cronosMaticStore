**Epic: Gestión del Carrito de Compras (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-013`
* **Título:** Backend - Modelo, Migración y Lógica Base para Carrito (`carts`, `cart_items`)
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.6
* **Descripción:**
    1.  Crear migraciones para las tablas `carts` y `cart_items` según el modelo de datos.
    2.  Crear modelos Eloquent `App\Models\Cart` y `App\Models\CartItem` con sus relaciones.
    3.  Implementar lógica en un `CartService` para:
        * Obtener/crear carrito para usuario autenticado o invitado (basado en `user_id` o `session_id`).
        * Añadir producto al carrito (verificar stock).
        * Actualizar cantidad de ítem.
        * Eliminar ítem.
        * Calcular totales del carrito.
        * Fusionar carrito de invitado al loguearse.
* **Criterios de Aceptación:**
    * Tablas `carts` y `cart_items` creadas.
    * Modelos funcionales.
    * `CartService` puede gestionar las operaciones básicas del carrito y la persistencia.
* **Prioridad:** Alta 
