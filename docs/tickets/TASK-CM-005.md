**Epic: Administración de la Tienda - Gestión de Categorías (Backend y API)**

* **ID del Ticket:** `TASK-CM-005`
* **Título:** Backend - Modelo, Migración, Factory y Seeders para Categorías
* **Historia(s) de Usuario Relacionada(s):** HU1.3, HU7.7, HU7.8, HU7.9, HU7.10
* **Descripción:**
    1.  Crear la migración para la tabla `categories` según el modelo de datos definido (id, name, slug, description, image_path, is_active, timestamps).
    2.  Crear el modelo Eloquent `App\Models\Category` con relaciones (ej. `products()`).
    3.  Crear un `CategoryFactory` para generar datos de prueba.
    4.  (Opcional) Crear un `CategorySeeder` para poblar con algunas categorías iniciales.
* **Criterios de Aceptación:**
    * La tabla `categories` se crea correctamente con todas sus columnas y restricciones.
    * El modelo `Category` está funcional.
    * Se pueden crear categorías usando la factory.
* **Prioridad:** Alta 
