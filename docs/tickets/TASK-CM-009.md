**Epic: Administración de la Tienda - Gestión de Productos (Backend y API)**

* **ID del Ticket:** `TASK-CM-009`
* **Título:** Backend - API Endpoints (CRUD) para Gestión de Productos (Protegido por Admin)
* **Historia(s) de Usuario Relacionada(s):** HU7.1, HU7.2, HU7.3, HU7.4, HU7.5, HU7.6
* **Descripción:**
    1.  Crear `Api/V1/Admin/ProductController.php`.
    2.  Implementar métodos para:
        * `index()`: Listar todos los productos (para admin, paginado, con filtros básicos).
        * `store()`: Crear nuevo producto (validar request, asociar `image_path` obtenido del endpoint de subida).
        * `show()`: Mostrar un producto específico.
        * `update()`: Actualizar un producto (validar request, asociar/actualizar `image_path`).
        * `destroy()`: Eliminar un producto (soft delete).
        * Métodos adicionales para actualizar stock o estado `is_active` si no se hace en `update()`.
    3.  Definir las rutas API RESTful para estos métodos bajo `/api/v1/admin/products` y protegerlas con `auth:sanctum` y `EnsureUserIsAdmin`.
    4.  Utilizar API Resources para formatear las respuestas JSON.
* **Criterios de Aceptación:**
    * Un admin puede listar, crear, ver, actualizar y eliminar productos a través de la API.
    * Las validaciones de datos funcionan.
    * La asociación de `image_path` funciona.
    * La gestión de stock y estado `is_active` funciona.
    * Usuarios no administradores no pueden acceder.
* **Prioridad:** Alta 
