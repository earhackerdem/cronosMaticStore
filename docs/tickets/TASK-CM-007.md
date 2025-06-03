**Epic: Administración de la Tienda - Gestión de Productos (Backend y API)**

* **ID del Ticket:** `TASK-CM-007`
* **Título:** Backend - Modelo, Migración, Factory y Seeders para Productos
* **Historia(s) de Usuario Relacionada(s):** HU1.1, HU1.2, HU1.5, HU7.1, HU7.2, HU7.3, HU7.4, HU7.5, HU7.6
* **Descripción:**
    1.  Crear la migración para la tabla `products` según el modelo de datos (id, category_id FK, name, slug, description, sku, price, stock_quantity, brand, movement_type, image_path, is_active, timestamps).
    2.  Crear el modelo Eloquent `App\Models\Product` con relaciones (ej. `category()`).
    3.  Crear un `ProductFactory` para generar datos de prueba.
    4.  (Opcional) Crear un `ProductSeeder` para poblar con algunos productos iniciales.
* **Criterios de Aceptación:**
    * La tabla `products` se crea correctamente con todas sus columnas y restricciones.
    * El modelo `Product` está funcional y sus relaciones definidas.
    * Se pueden crear productos usando la factory.
* **Prioridad:** Alta 
