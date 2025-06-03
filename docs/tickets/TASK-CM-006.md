**Epic: Administración de la Tienda - Gestión de Categorías (Backend y API)**

* **ID del Ticket:** `TASK-CM-006`
* **Título:** Backend - API Endpoints (CRUD) para Gestión de Categorías (Protegido por Admin)
* **Historia(s) de Usuario Relacionada(s):** HU7.7, HU7.8, HU7.9, HU7.10
* **Descripción:**
    1.  Crear `Api/V1/Admin/CategoryController.php`.
    2.  Implementar métodos para:
        * `index()`: Listar todas las categorías (para admin, paginado).
        * `store()`: Crear nueva categoría (validar request, manejar subida de `image_path` si se provee).
        * `show()`: Mostrar una categoría específica.
        * `update()`: Actualizar una categoría (validar request, manejar subida/actualización de `image_path`).
        * `destroy()`: Eliminar una categoría (soft delete si se implementa, o borrado con validación de productos asociados).
    3.  Definir las rutas API RESTful para estos métodos bajo `/api/v1/admin/categories` y protegerlas con `auth:sanctum` y el middleware `EnsureUserIsAdmin`.
    4.  Utilizar API Resources para formatear las respuestas JSON.
* **Criterios de Aceptación:**
    * Un admin puede listar, crear, ver, actualizar y eliminar categorías a través de la API.
    * Las validaciones de datos funcionan correctamente.
    * La gestión de `image_path` (guardar ruta) funciona si se implementa subida.
    * Usuarios no administradores no pueden acceder a estos endpoints.
* **Prioridad:** Alta 
