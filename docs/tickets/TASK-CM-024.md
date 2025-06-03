**Epic: Administración de la Tienda - UI (Frontend React)**

* **ID del Ticket:** `TASK-CM-024`
* **Título:** Frontend (Admin) - Layout Básico y Autenticación para Panel de Administración
* **Historia(s) de Usuario Relacionada(s):** HU7.0
* **Descripción:**
    1.  Crear un layout base para el panel de administración en React (`AdminLayout.tsx`).
    2.  Implementar lógica de enrutamiento en React para las secciones de admin (ej. `/admin/products`, `/admin/categories`, `/admin/orders`).
    3.  Asegurar que estas rutas estén protegidas y solo sean accesibles si el usuario está autenticado vía API (token Sanctum) y tiene `is_admin = true`.
    4.  El login de admin puede usar el mismo endpoint `POST /api/v1/auth/login`, pero el frontend redirigirá a `/admin/dashboard` si `user.is_admin` es true.
* **Criterios de Aceptación:**
    * Existe un layout para el panel de admin.
    * Solo usuarios administradores pueden acceder a las rutas del panel de admin.
* **Dependencias:** `TASK-CM-002`, `TASK-CM-004`
* **Prioridad:** Alta 
