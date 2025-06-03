**Epic: Administración de la Tienda - UI (Frontend React)**

* **ID del Ticket:** `TASK-CM-026`
* **Título:** Frontend (Admin) - UI para Gestión de Categorías (CRUD Básico)
* **Historia(s) de Usuario Relacionada(s):** HU7.7, HU7.8, HU7.9, HU7.10
* **Descripción:**
    1.  Desarrollar componentes React para:
        * Listar categorías (`AdminCategoriesListPage.tsx`) con acciones (editar, eliminar).
        * Formulario de creación/edición de categoría (`AdminCategoryFormPage.tsx`) con nombre, descripción e imagen opcional (integrar con `TASK-CM-008`).
    2.  Integrar con los endpoints API de admin para categorías (`TASK-CM-006`).
* **Criterios de Aceptación:**
    * El administrador puede listar, crear, editar y eliminar categorías.
    * Se maneja la subida opcional de imagen para categorías.
* **Dependencias:** `TASK-CM-006`, `TASK-CM-008`, `TASK-CM-024`
* **Prioridad:** Media 
