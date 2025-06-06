**Epic: Gestión del Carrito de Compras (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-014`
* **Título:** Backend - API Endpoints para Gestión del Carrito
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.4
* **Descripción:**
    1.  Crear `Api/V1/CartController.php` con inyección de `CartService`.
    2.  Implementar métodos RESTful usando `CartService` para:
        * `show()`: `GET /api/v1/cart` - Obtener carrito actual con ítems.
        * `addItem()`: `POST /api/v1/cart/items` - Añadir producto al carrito.
        * `updateItem()`: `PUT /api/v1/cart/items/{cart_item_id}` - Actualizar cantidad de ítem.
        * `removeItem()`: `DELETE /api/v1/cart/items/{cart_item_id}` - Eliminar ítem del carrito.
        * `clear()`: `DELETE /api/v1/cart` - Vaciar carrito completamente.
    3.  Crear Form Requests para validación:
        * `AddCartItemRequest.php` - Validar producto_id, cantidad, etc.
        * `UpdateCartItemRequest.php` - Validar cantidad mínima/máxima.
    4.  Crear API Resources para respuestas consistentes:
        * `CartResource.php` - Formatear carrito con totales y metadatos.
        * `CartItemResource.php` - Formatear ítems con detalles del producto.
    5.  Implementar middleware para identificación de carrito:
        * Usuarios autenticados: usar `user_id` con Sanctum.
        * Invitados: usar `session_id` desde header o cookie.
    6.  Añadir manejo de errores específicos:
        * Stock insuficiente, producto inactivo, carrito no encontrado.
        * Respuestas JSON consistentes con códigos HTTP apropiados.
    7.  Implementar rutas en `routes/api.php` con grupos y middleware.
* **Criterios de Aceptación:**
    * ✅ Todos los endpoints del carrito funcionan según especificaciones REST.
    * ✅ Manejo correcto de carritos para usuarios autenticados e invitados.
    * ✅ Validaciones de stock, producto activo y permisos aplicadas.
    * ✅ Respuestas JSON consistentes con API Resources.
    * ✅ Form Requests validan entrada de datos correctamente.
    * ✅ Códigos de estado HTTP apropiados (200, 201, 400, 404, 422).
    * ✅ Tests Feature completos para todos los endpoints.
    * ✅ Documentación de API actualizada.
    * ✅ Manejo de errores con mensajes descriptivos en español.
* **Tareas Técnicas Específicas:**
    1. **Controlador**: `app/Http/Controllers/Api/V1/CartController.php`
    2. **Form Requests**: `app/Http/Requests/Api/V1/AddCartItemRequest.php`, `UpdateCartItemRequest.php`
    3. **API Resources**: `app/Http/Resources/Api/V1/CartResource.php`, `CartItemResource.php`
    4. **Rutas**: Grupo `/api/v1/cart` en `routes/api.php`
    5. **Tests**: `tests/Feature/Api/V1/CartControllerTest.php` con casos edge
    6. **Middleware**: Lógica de identificación de carrito (usuario vs sesión)
* **Casos de Prueba Mínimos:**
    * ✅ Obtener carrito vacío para usuario nuevo
    * ✅ Añadir producto válido al carrito
    * ✅ Añadir producto con stock insuficiente (error 422)
    * ✅ Añadir producto inactivo (error 400)
    * ✅ Actualizar cantidad de ítem existente
    * ✅ Actualizar ítem inexistente (error 404)
    * ✅ Eliminar ítem del carrito
    * ✅ Vaciar carrito completamente
    * ✅ Acceso de invitado con session_id
    * ✅ Fusión automática de carrito al autenticarse
* **Consideraciones de Seguridad:**
    * Validar permisos: usuarios solo pueden acceder a su carrito
    * Sanitizar entrada de datos en Form Requests
    * Rate limiting en endpoints de modificación
    * Verificar ownership de cart_items antes de modificar
* **Consideraciones de Rendimiento:**
    * Eager loading de relaciones (cart.items.product)
    * Caché de consultas frecuentes si es necesario
    * Optimizar consultas de validación de stock
* **Dependencias:** `TASK-CM-013` (Modelos y CartService implementados)
* **Prioridad:** Alta
* **Estimación:** 8-10 horas de desarrollo + testing
