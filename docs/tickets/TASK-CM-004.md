**Epic: Administración de la Tienda - Modelo de Datos y Roles**

* **ID del Ticket:** `TASK-CM-004`
* **Título:** Modelo de Datos - Añadir campo `is_admin` a tabla `users` y Migración
* **Historia(s) de Usuario Relacionada(s):** HU7.0 y todas las HU de Admin (Epic 7)
* **Descripción:**
    1.  Crear una nueva migración para añadir una columna `is_admin` (BOOLEAN, DEFAULT `false`, NOT NULL) a la tabla `users`.
    2.  Ejecutar la migración.
    3.  Actualizar el modelo `App\Models\User.php` para incluir `is_admin` en `$fillable` si se gestionará masivamente y en `$casts`.
    4.  Crear un middleware `EnsureUserIsAdmin` que verifique si el usuario autenticado tiene `is_admin = true`.
* **Criterios de Aceptación:**
    * La columna `is_admin` existe en la tabla `users` con el valor por defecto `false`.
    * El modelo User refleja el nuevo campo.
    * El middleware `EnsureUserIsAdmin` puede ser aplicado a rutas/controladores y deniega el acceso si el usuario no es admin.
    * Se puede asignar manually un usuario como administrador para pruebas.
* **Prioridad:** Alta 
