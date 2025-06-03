**Epic: Navegación y Descubrimiento de Productos (API Pública y Frontend)**

* **ID del Ticket:** `TASK-CM-010`
* **Título:** Backend - API Endpoints Públicos para Visualización de Categorías y Productos
* **Historia(s) de Usuario Relacionada(s):** HU1.1, HU1.2, HU1.3, HU1.4, HU1.5
* **Descripción:**
    1.  Crear `Api/V1/CategoryController.php` (para la parte pública). Implementar `index()` y `show()` que devuelvan categorías activas y, en `show()`, los productos asociados (paginados y activos).
    2.  Crear `Api/V1/ProductController.php` (para la parte pública). Implementar `index()` (con paginación, filtros por categoría, búsqueda básica, ordenamiento) y `show()` (por slug). Solo devolver productos activos.
    3.  Definir las rutas públicas `GET /api/v1/categories`, `GET /api/v1/categories/{slug}`, `GET /api/v1/products`, `GET /api/v1/products/{slug}`.
    4.  Usar API Resources para formatear las respuestas.
* **Criterios de Aceptación:**
    * `GET /api/v1/categories` devuelve lista de categorías activas.
    * `GET /api/v1/categories/{slug}` devuelve detalles de la categoría y sus productos activos paginados.
    * `GET /api/v1/products` devuelve lista de productos activos paginados, con filtros y ordenamiento funcionando.
    * `GET /api/v1/products/{slug}` devuelve detalles de un producto activo.
    * Los endpoints manejan correctamente casos de "no encontrado" (404).
* **Prioridad:** Alta 
