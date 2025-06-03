**Epic: Gestión de Direcciones (Backend, API y Frontend - Usuario)**

* **ID del Ticket:** `TASK-CM-016`
* **Título:** Backend - Modelo, Migración y API (CRUD) para Libreta de Direcciones del Usuario
* **Historia(s) de Usuario Relacionada(s):** HU3.3, HU3.5, HU6.1 a HU6.6
* **Descripción:**
    1.  Crear migración para la tabla `addresses` según el modelo de datos.
    2.  Crear modelo Eloquent `App\Models\Address` con relación a `User`.
    3.  Crear `Api/V1/User/AddressController.php` con métodos CRUD (`index`, `store`, `update`, `destroy`) y para marcar como default (shipping/billing).
    4.  Definir rutas API bajo `/api/v1/user/addresses` protegidas por Sanctum.
    5.  Usar API Resources.
* **Criterios de Aceptación:**
    * Un usuario autenticado puede gestionar su libreta de direcciones vía API.
    * Las validaciones y la lógica de "dirección por defecto" funcionan.
* **Prioridad:** Media (Necesario para Checkout, pero no bloquea la visualización inicial de productos/carrito) 
