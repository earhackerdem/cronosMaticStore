**Bug/Mejora: Implementar Soft Deletes para Productos y Categorías**

**Descripción:**
Los modelos Product y Category no implementan Soft Deletes, realizando eliminaciones permanentes que podrían causar pérdida de datos y problemas de integridad referencial. Existen comentarios en el código que sugieren que esto debería implementarse.

**Problemas Identificados:**
1. `Product::delete()` realiza eliminación permanente en lugar de soft delete
2. `Category::delete()` realiza eliminación permanente sin validar productos asociados
3. Las migraciones no incluyen columna `deleted_at`
4. Los modelos no usan el trait `SoftDeletes`
5. Los controladores tienen comentarios sobre implementar soft deletes pero no está implementado
6. Riesgo de pérdida de datos históricos e integridad referencial

**Archivos Afectados:**
- `app/Models/Product.php`
- `app/Models/Category.php`
- `database/migrations/2025_05_30_164215_create_products_table.php`
- `database/migrations/2025_05_29_220120_create_categories_table.php`
- `app/Http/Controllers/Api/V1/Admin/ProductController.php`
- `app/Http/Controllers/Api/V1/Admin/CategoryController.php`

**Solución Propuesta:**
1. Crear migraciones para añadir columna `deleted_at` a tablas products y categories
2. Implementar trait `SoftDeletes` en modelos Product y Category
3. Actualizar controladores para usar soft deletes apropiadamente
4. Añadir validación en CategoryController para prevenir eliminación con productos asociados
5. Actualizar queries para filtrar elementos eliminados en endpoints públicos
6. Considerar endpoint para restaurar elementos eliminados (admin)

**Prioridad:** Media - Importante para integridad de datos y posible recuperación de información 
