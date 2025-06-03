**Epic: Gestión del Carrito de Compras (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-015`
* **Título:** Frontend (React) - UI y Lógica para Interacción con el Carrito de Compras
* **Historia(s) de Usuario Relacionada(s):** HU2.1 a HU2.6
* **Descripción:**
    1.  Desarrollar componentes React para:
        * Botón "Añadir al Carrito" en páginas de producto.
        * Indicador de carrito en la cabecera (conteo de ítems, enlace a la página del carrito).
        * Página del Carrito (`CartPage.tsx`) mostrando ítems, cantidades, precios, subtotal, opciones para actualizar cantidad y eliminar ítems.
    2.  Integrar estos componentes con los endpoints de la API del carrito (`TASK-CM-014`).
    3.  Manejar el estado global del carrito en React (ej. con Context API o Zustand/Redux si se prefiere).
* **Criterios de Aceptación:**
    * El cliente puede añadir productos, ver su carrito, modificar cantidades y eliminar ítems.
    * El carrito se actualiza visualmente y refleja los cambios hechos vía API.
    * Se cumplen los AC de HU2.1 a HU2.6 relacionados con la UI.
* **Dependencias:** `TASK-CM-014`
* **Prioridad:** Alta 
