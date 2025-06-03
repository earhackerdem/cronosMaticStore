**Epic: Gestión del Carrito de Compras (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-014`
* **Título:** Backend - API Endpoints para Gestión del Carrito
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.4
* **Descripción:**
    1.  Crear `Api/V1/CartController.php`.
    2.  Implementar métodos usando `CartService` para:
        * `show()`: `GET /api/v1/cart` - Obtener carrito actual.
        * `addItem()`: `POST /api/v1/cart/items` - Añadir ítem.
        * `updateItem()`: `PUT /api/v1/cart/items/{cart_item_id}` - Actualizar cantidad.
        * `removeItem()`: `DELETE /api/v1/cart/items/{cart_item_id}` - Eliminar ítem.
    3.  Proteger los endpoints (Sanctum para usuarios, manejo de sesión para invitados).
    4.  Usar API Resources para formatear respuestas del carrito.
* **Criterios de Aceptación:**
    * Todos los endpoints del carrito funcionan según las especificaciones de API.
    * Se maneja correctamente la identificación de carritos de invitados y usuarios logueados.
    * Las validaciones de stock se aplican.
* **Dependencias:** `TASK-CM-013`
* **Prioridad:** Alta 
