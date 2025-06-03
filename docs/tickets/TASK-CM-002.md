**Epic: Autenticación (API y Funcionalidad de Usuario)**

* **ID del Ticket:** `TASK-CM-002`
* **Título:** API Endpoints (Sanctum) para Registro e Inicio de Sesión de Usuarios
* **Historia(s) de Usuario Relacionada(s):** HU4.1, HU4.2
* **Descripción:**
    1.  Crear `Api/V1/Auth/RegisteredUserController.php` con un método `store` para registrar nuevos usuarios. Validar datos (nombre, email único, password confirmado). Devolver el objeto usuario y un token Sanctum.
    2.  Crear `Api/V1/Auth/AuthenticatedSessionController.php` con un método `store` para iniciar sesión. Validar credenciales. Devolver el objeto usuario y un token Sanctum.
    3.  Definir las rutas `POST /api/v1/auth/register` y `POST /api/v1/auth/login` en `routes/api.php`.
* **Criterios de Aceptación:**
    * `POST /api/v1/auth/register` con datos válidos crea un usuario, devuelve 201 y un token.
    * `POST /api/v1/auth/register` con email duplicado o datos inválidos devuelve 422 con errores.
    * `POST /api/v1/auth/login` con credenciales válidas devuelve 200 y un token.
    * `POST /api/v1/auth/login` con credenciales inválidas devuelve 401 o 422.
* **Prioridad:** Alta 
