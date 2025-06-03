**Epic: Navegación y Descubrimiento de Productos (API Pública y Frontend)**

* **ID del Ticket:** `TASK-CM-012`
* **Título:** Frontend (React) - Página de Detalle de Producto
* **Historia(s) de Usuario Relacionada(s):** HU1.2, HU1.5
* **Descripción:**
    1.  Desarrollar el componente React para la página de detalle de producto (`ProductShowPage.tsx`).
    2.  Integrar con el endpoint `GET /api/v1/products/{slug}`.
    3.  Mostrar toda la información detallada del producto (nombre, descripción, precio, imagen, marca, tipo de movimiento, stock).
    4.  Incluir botón "Añadir al carrito" (funcionalidad se desarrolla en ticket de carrito).
* **Criterios de Aceptación:**
    * Se muestran todos los detalles del producto según AC de HU1.2 y HU1.5.
    * Si el producto no existe, se maneja el error elegantemente.
* **Dependencias:** `TASK-CM-010`
* **Prioridad:** Alta 
