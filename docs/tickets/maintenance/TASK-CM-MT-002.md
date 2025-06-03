**Bug/Mejora: Form Requests Faltantes para Endpoints Públicos de API**

**Descripción:**
Los controladores públicos de la API (CategoryController y ProductController en V1) no utilizan Form Requests para validación de parámetros de consulta, lo que puede llevar a inconsistencias en validación y manejo de errores.

**Problemas Identificados:**
1. `CategoryController::index()` no valida parámetros de paginación
2. `CategoryController::show()` no valida el parámetro slug
3. `ProductController::index()` no valida parámetros de filtrado (category, search, sortBy, sortDirection, per_page)
4. `ProductController::show()` no valida el parámetro slug
5. Validaciones realizadas directamente en controladores en lugar de Form Requests dedicados

**Archivos Afectados:**
- `app/Http/Controllers/Api/V1/CategoryController.php`
- `app/Http/Controllers/Api/V1/ProductController.php`
- Falta crear: `app/Http/Requests/Api/V1/ListCategoriesRequest.php`
- Falta crear: `app/Http/Requests/Api/V1/ShowCategoryRequest.php`  
- Falta crear: `app/Http/Requests/Api/V1/ListProductsRequest.php`
- Falta crear: `app/Http/Requests/Api/V1/ShowProductRequest.php`

**Solución Propuesta:**
1. Crear Form Requests para validar parámetros de consulta en endpoints públicos
2. Implementar validaciones consistentes para filtros, ordenamiento y paginación
3. Centralizar manejo de errores de validación
4. Documentar parámetros válidos para cada endpoint

**Prioridad:** Baja - Funcionalidad actual es correcta pero falta consistencia en validación 
