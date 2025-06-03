**Epic: Proceso de Compra (Checkout) y Pedidos (Backend, API y Frontend)**

* **ID del Ticket:** `TASK-CM-021`
* **Título:** Backend - Notificaciones por Email (Confirmación de Pedido)
* **Historia(s) de Usuario Relacionada(s):** HU3.10
* **Descripción:**
    1.  Crear una clase Mailable de Laravel para la confirmación de pedido (`OrderConfirmationMail.php`).
    2.  Diseñar una plantilla de email simple (Blade o Markdown) para la confirmación, incluyendo detalles del pedido.
    3.  Integrar el envío de este email en el `OrderService` después de que un pedido se haya completado y pagado exitosamente.
* **Criterios de Aceptación:**
    * Se envía un correo de confirmación al cliente tras una compra exitosa.
    * El correo contiene la información relevante del pedido.
* **Dependencias:** `TASK-CM-018`
* **Prioridad:** Alta 
