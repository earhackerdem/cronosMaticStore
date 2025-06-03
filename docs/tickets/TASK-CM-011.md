**Epic: Navegación y Descubrimiento de Productos (API Pública y Frontend)**

* **ID del Ticket:** `TASK-CM-011`
* **Título:** Frontend (React) - Página de Listado de Productos y Navegación por Categorías
* **Historia(s) de Usuario Relacionada(s):** HU1.1, HU1.3, HU1.4, HU1.5
* **Descripción:**
    1.  Desarrollar el componente React para la página de listado de productos (`ProductsIndexPage.tsx`).
    2.  Integrar con el endpoint `GET /api/v1/products` para mostrar los productos.
    3.  Implementar paginación en el frontend.
    4.  Implementar UI para filtros (lista de categorías obtenida de `GET /api/v1/categories`) y búsqueda básica, que actualicen las llamadas a la API.
    5.  Mostrar imagen, nombre, precio, marca, y disponibilidad de stock para cada producto.
    6.  Asegurar que los enlaces a detalle de producto y "Añadir al carrito" estén presentes.
* **Criterios de Aceptación:**
    * Se muestran los productos según los AC de HU1.1, HU1.3, HU1.4, HU1.5.
    * La paginación funciona.
    * El filtrado por categoría y la búsqueda actualizan la lista de productos.
* **Dependencias:** `TASK-CM-010`
* **Prioridad:** Alta 
