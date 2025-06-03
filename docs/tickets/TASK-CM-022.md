**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-022`
* **Título:** Frontend (React) - Flujo de Checkout Completo
* **Historia(s) de Usuario Relacionada(s):** HU3.1 a HU3.9, HU3.11
* **Descripción:**
    1.  Desarrollar los componentes React para las páginas/pasos del checkout (`CheckoutPage.tsx`):
        * Ingreso/selección de dirección de envío (integrar con `TASK-CM-017` si usuario logueado).
        * Ingreso/selección de dirección de facturación.
        * Selección de método de envío (para MVP, puede ser fijo o una opción simple).
        * Resumen del pedido.
        * Integración con el SDK de PayPal para obtener el nonce/detalles de pago.
    2.  Llamar al endpoint `POST /api/v1/orders` con todos los datos recopilados.
    3.  Manejar la respuesta y redirigir a la página de confirmación de pedido o mostrar errores.
* **Criterios de Aceptación:**
    * El cliente puede completar todos los pasos del checkout.
    * Los datos se envían correctamente a la API de creación de pedido.
    * La integración con PayPal (Sandbox) funciona en el frontend.
    * Se muestra la página de confirmación o errores.
* **Dependencias:** `TASK-CM-015` (para iniciar checkout), `TASK-CM-016` (opcional para direcciones), `TASK-CM-017` (opcional para direcciones), `TASK-CM-020` (API de pedidos)
* **Prioridad:** Muy Alta 
