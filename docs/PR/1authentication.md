
**Título:** Implementación de Autenticación API v1 con Sanctum

**Descripción:**

Esta Pull Request introduce el sistema de autenticación basado en tokens utilizando Laravel Sanctum para la API v1. Se han completado los siguientes tickets de trabajo:

*   **TASK-CM-001: Configuración Fundacional de API RESTful y Autenticación Sanctum**
    *   Se ha instalado y configurado Laravel Sanctum.
    *   Se publicaron y ejecutaron las migraciones de Sanctum.
    *   Se definió el grupo de rutas `/api/v1`.
    *   Las rutas protegidas utilizan el middleware `auth:sanctum`.
    *   Se configuró `config/cors.php` para permitir peticiones del frontend (con `supports_credentials => true`).
    *   Se crearon endpoints de prueba (`/api/v1/status` y `/api/v1/auth-status`) para verificar la configuración.
    *   Las respuestas JSON de la API siguen las convenciones definidas.

*   **TASK-CM-002: API Endpoints (Sanctum) para Registro e Inicio de Sesión de Usuarios**
    *   Se implementó el endpoint `POST /api/v1/auth/register` para el registro de nuevos usuarios.
        *   Valida los datos de entrada (nombre, email único, contraseña confirmada).
        *   Devuelve el objeto del usuario y un token Sanctum en formato `{"data": {"user": {...}, "token": "..."}}` con estado 201.
    *   Se implementó el endpoint `POST /api/v1/auth/login` para el inicio de sesión.
        *   Valida credenciales (email, contraseña).
        *   Devuelve el objeto del usuario y un token Sanctum en formato `{"data": {"user": {...}, "token": "..."}}` con estado 200.
        *   Maneja credenciales incorrectas con respuesta 422.

*   **TASK-CM-003: API Endpoints (Sanctum) para Logout y Obtener Usuario Autenticado**
    *   Se implementó el endpoint `POST /api/v1/auth/logout` (protegido por Sanctum) que invalida el token actual del usuario y devuelve una respuesta 204.
    *   Se implementó el endpoint `GET /api/v1/auth/user` (protegido por Sanctum) que devuelve los datos del usuario autenticado en formato `{"data": {...}}`.

**Cambios Realizados:**

*   **Dependencias:**
    *   Añadido `laravel/sanctum` a `composer.json`.
*   **Configuración:**
    *   Publicado y configurado `config/sanctum.php`.
    *   Ajustado `config/cors.php` para las rutas API y credenciales.
*   **Base de Datos:**
    *   Añadida la migración para la tabla `personal_access_tokens`.
*   **Rutas:**
    *   Definidas las rutas de API v1 en `routes/api.php`, incluyendo rutas públicas y protegidas con `auth:sanctum` para autenticación, registro, login, logout y obtención de datos del usuario.
*   **Controladores:**
    *   Creado `app/Http/Controllers/Api/V1/HealthCheckController.php` con métodos `status` y `authStatus`.
    *   Creado `app/Http/Controllers/Api/V1/AuthController.php` con métodos `register`, `login`, `logout`, y `user`, asegurando que las respuestas JSON cumplan con las especificaciones de la API.

**Pruebas (Manuales/Automatizadas):**

*   Verificar que el endpoint `GET /api/v1/status` responde correctamente (200 OK).
*   Verificar que los endpoints protegidos (`/api/v1/auth-status`, `/api/v1/auth/user`, `/api/v1/auth/logout`) devuelven 401 si no se provee token.
*   Probar el flujo de registro (`POST /api/v1/auth/register`):
    *   Con datos válidos: debe crear el usuario y devolver token (201).
    *   Con email duplicado o datos inválidos: debe devolver error 422.
*   Probar el flujo de login (`POST /api/v1/auth/login`):
    *   Con credenciales válidas: debe devolver token (200).
    *   Con credenciales inválidas: debe devolver error 422/401.
*   Probar la obtención de datos del usuario (`GET /api/v1/auth/user`) con un token válido.
*   Probar el logout (`POST /api/v1/auth/logout`) con un token válido: debe invalidar el token (204).
*   Verificar que después del logout, el token ya no es válido para acceder a rutas protegidas.

**Nota Importante para Despliegue a Producción:**

*   **Configuración CORS:** Antes de desplegar a un entorno de producción, es crucial revisar y ajustar la configuración en `config/cors.php`. Específicamente, el parámetro `'allowed_origins'` debe cambiarse de `['*']` a una lista explícita de los dominios del frontend que consumirán la API. Esto es una medida de seguridad importante para prevenir el acceso no autorizado desde orígenes desconocidos.

Esta propuesta debería cubrir los aspectos más importantes para la Pull Request. ¿Hay algo más en lo que pueda ayudarte?
