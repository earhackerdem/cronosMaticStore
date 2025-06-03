**Epic: Autenticación (API y Funcionalidad de Usuario)**

* **ID del Ticket:** `TASK-CM-003`
* **Título:** API Endpoints (Sanctum) para Logout y Obtener Usuario Autenticado
* **Historia(s) de Usuario Relacionada(s):** HU4.3, HU4.4 (parcialmente, para obtener datos)
* **Descripción:**
    1.  Modificar/crear `Api/V1/Auth/AuthenticatedSessionController.php` para incluir un método `destroy` que invalide el token Sanctum actual del usuario.
    2.  Crear un método (ej. en `Api/V1/Auth/ProfileController.php` o similar) para obtener los datos del usuario autenticado actualmente (`GET /api/v1/auth/user`).
    3.  Definir las rutas `POST /api/v1/auth/logout` (protegida por `auth:sanctum`) y `GET /api/v1/auth/user` (protegida por `auth:sanctum`).
* **Criterios de Aceptación:**
    * `POST /api/v1/auth/logout` con token válido invalida el token y devuelve 204.
    * `GET /api/v1/auth/user` con token válido devuelve los datos del usuario autenticado (ej. id, name, email).
    * Ambos endpoints devuelven 401 si no se provee token o es inválido.
* **Prioridad:** Alta 
