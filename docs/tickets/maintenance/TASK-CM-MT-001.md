**Bug/Mejora: Inconsistencias en Namespaces de API Resources**

**Descripción:**
Se han identificado inconsistencias en la estructura de namespaces de los API Resources que pueden causar confusión y mantenimiento problemático.

**Problemas Identificados:**
1. Estructura de directorios duplicada: `V1/` y `Api/V1/`
2. CategoryResource duplicado en 2 ubicaciones
3. ProductResource duplicado en 2 ubicaciones  
4. Namespaces inconsistentes entre controladores públicos y admin
5. Diferencias en formato de respuesta entre Resources duplicados

**Archivos Afectados:**
- `app/Http/Resources/V1/CategoryResource.php`
- `app/Http/Resources/Api/V1/CategoryResource.php` 
- `app/Http/Resources/V1/ProductResource.php`
- `app/Http/Resources/Api/V1/Admin/ProductResource.php`
- Controladores que importan estos Resources

**Solución Propuesta:**
Unificar bajo un solo namespace `App\Http\Resources\Api\V1\` y crear versiones específicas para Admin cuando sea necesario.

**Prioridad:** Media - No afecta funcionalidad pero puede causar confusión en desarrollo futuro