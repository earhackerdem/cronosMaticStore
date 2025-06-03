**Epic: Administración de la Tienda - UI (Frontend React)**

* **ID del Ticket:** `TASK-CM-025`
* **Título:** Frontend (Admin) - UI para Gestión de Productos (CRUD)
* **Historia(s) de Usuario Relacionada(s):** HU7.1, HU7.2, HU7.3, HU7.4, HU7.5, HU7.6
* **Descripción:**
    1.  Desarrollar componentes React para:
        * Listar productos (`AdminProductsListPage.tsx`) con paginación, búsqueda/filtros básicos y acciones (editar, eliminar, cambiar estado).
        * Formulario de creación/edición de producto (`AdminProductFormPage.tsx`) con todos los campos, incluida la subida de imagen (integrar con `TASK-CM-008`).
    2.  Integrar con los endpoints API de admin para productos (`TASK-CM-009`).
* **Criterios de Aceptación:**
    * El administrador puede listar, crear, editar (incluida imagen y stock), activar/desactivar y eliminar (soft delete) productos a través de la UI.
    * Las validaciones de formulario se muestran al usuario.
* **Dependencias:** `TASK-CM-009`, `TASK-CM-008`, `TASK-CM-024`
* **Prioridad:** Alta 
