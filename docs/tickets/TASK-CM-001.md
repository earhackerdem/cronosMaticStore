**Ticket #1: Configuración Fundacional de API RESTful para E-commerce y Autenticación Sanctum**

* **ID del Ticket:** `TASK-CM-001`
* **Título:** Establecer la infraestructura base para la API RESTful v1 y configurar Laravel Sanctum para autenticación SPA.
* **Historia(s) de Usuario Relacionada(s):** Habilita indirectamente todas las HU que dependen de la API (Cliente y Admin). Específicamente necesario para HU4.2 (login vía API), HU4.1 (registro vía API), y todas las HU de Admin (HU7.x) que requerirán endpoints API protegidos.
* **Descripción:**
    1.  Instalar y configurar Laravel Sanctum para proveer autenticación basada en tokens para la Single Page Application (React). Esto incluye publicar y ejecutar migraciones de Sanctum.
    2.  Definir un nuevo grupo de rutas en `routes/api.php` con el prefijo `/v1` para todos los endpoints de e-commerce.
    3.  Asegurar que las rutas dentro de `/api/v1/` que requieran autenticación utilicen el middleware `auth:sanctum`.
    4.  Configurar `config/cors.php` para permitir peticiones desde el frontend (considerar `supports_credentials => true` y los dominios permitidos, inicialmente el mismo dominio de la app).
    5.  Ajustar el middleware `EnsureFrontendRequestsAreStateful` en `app/Http/Kernel.php` para el grupo `api` si se va a utilizar la autenticación de SPA basada en cookies de Sanctum (aunque se priorizará tokens, la configuración base es similar).
    6.  Establecer convenciones para las respuestas JSON de la API (estructura para datos exitosos y errores, como se definió en las Especificaciones de API).
    7.  Crear un controlador de prueba (ej. `Api/V1/HealthCheckController.php`) con un endpoint público (ej. `GET /api/v1/status`) y un endpoint protegido por Sanctum (ej. `GET /api/v1/auth-status`) para verificar la configuración.
* **Criterios de Aceptación:**
    * Se puede instalar y configurar Sanctum sin errores.
    * Las migraciones de Sanctum se ejecutan correctamente.
    * Un endpoint público de prueba bajo `/api/v1/` responde correctamente (ej. `GET /api/v1/status` devuelve `{"status": "ok"}`).
    * Un endpoint protegido de prueba bajo `/api/v1/` devuelve error 401 si no se provee token.
    * Un endpoint protegido de prueba bajo `/api/v1/` devuelve una respuesta exitosa (ej. 200) si se provee un token Sanctum válido (obtenido tras un login/registro vía API, a desarrollar en ticket posterior).
    * La configuración CORS permite peticiones desde el dominio del frontend.
* **Prioridad:** **Más Alta**
* **Notas Adicionales:** Este ticket es un bloqueador para la mayoría de las funcionalidades de e-commerce. Se deben seguir las Especificaciones de API definidas previamente para el manejo de errores y formato de respuesta. Considerar que el starter kit ya tiene autenticación web con Inertia; esta configuración de Sanctum es para la API que consumirá el frontend React de forma más directa para las operaciones de e-commerce. 
