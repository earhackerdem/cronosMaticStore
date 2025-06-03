**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-023`
* **Título:** Frontend (React) - Páginas "Mis Pedidos" y Detalle de Pedido para Usuario
* **Historia(s) de Usuario Relacionada(s):** HU5.1, HU5.2
* **Descripción:**
    1.  Crear API endpoints en backend: `GET /api/v1/user/orders` y `GET /api/v1/user/orders/{order_number}` (protegidos por Sanctum, el usuario solo ve sus pedidos).
    2.  Desarrollar componentes React para la sección "Mis Pedidos" del perfil del usuario:
        * Página de listado de pedidos (`UserOrdersPage.tsx`).
        * Página de detalle de pedido (`UserOrderDetailPage.tsx`).
    3.  Integrar con los nuevos endpoints API.
* **Criterios de Aceptación:**
    * Un usuario autenticado puede ver su historial de pedidos y el detalle de cada uno.
* **Dependencias:** `TASK-CM-003`, `TASK-CM-018`
* **Prioridad:** Media 
