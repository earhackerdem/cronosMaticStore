**Epic: Administración de la Tienda - UI (Frontend React)**

* **ID del Ticket:** `TASK-CM-027`
* **Título:** Frontend (Admin) - UI para Visualización de Pedidos y Actualización de Estado
* **Historia(s) de Usuario Relacionada(s):** HU7.11, HU7.12, HU7.13
* **Descripción:**
    1.  Crear API Endpoints para Administración de Pedidos (`GET /api/v1/admin/orders`, `GET /api/v1/admin/orders/{id}`, `PUT /api/v1/admin/orders/{id}/status`) protegidos por admin.
    2.  Desarrollar componentes React para:
        * Listar todos los pedidos (`AdminOrdersListPage.tsx`) con paginación y filtros básicos.
        * Ver detalle de un pedido (`AdminOrderDetailPage.tsx`).
        * Modificar el estado de un pedido.
    3.  Integrar con los nuevos endpoints API de admin para pedidos.
* **Criterios de Aceptación:**
    * El administrador puede listar todos los pedidos y ver sus detalles.
    * El administrador puede cambiar el estado de un pedido.
* **Dependencias:** `TASK-CM-018` (modelos de pedido), `TASK-CM-024`
* **Prioridad:** Media 
