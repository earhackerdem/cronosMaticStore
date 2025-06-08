# Discrepancias Adicionales del Backend - CronosMaticStore

## Fecha: 11 de Enero de 2025

### Discrepancias Encontradas en Revisión del Backend

Después de la actualización inicial de la documentación, se realizó una revisión exhaustiva del código backend y se encontraron las siguientes discrepancias adicionales:

## 🔍 **Discrepancias Críticas**

### 1. Precisión Decimal en Productos

**📖 Documentación Actualizada:** `DECIMAL(10, 2)` para price
**⚙️ Implementación Real:** `DECIMAL(8, 2)` en migración

**Archivo:** `database/migrations/2025_05_30_164215_create_products_table.php`
```php
$table->decimal('price', 8, 2);
```

**Impacto:** 
- Documentación permite precios hasta 99,999,999.99
- Implementación permite precios hasta 999,999.99
- Para relojes de lujo, esto podría ser limitante

**Recomendación:** Mantener `DECIMAL(8, 2)` ya que es suficiente para el MVP y relojes típicos

## 🔧 **Discrepancias de Arquitectura**

### 2. Rutas de API de Direcciones

**📖 Documentación:** Rutas en `/api/v1/user/addresses` con autenticación Sanctum
**⚙️ Implementación Real:** Rutas en `web.php` con autenticación web/session

**Ubicación Real:**
```php
// En routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('api/v1/user')->group(function () {
        Route::get('/addresses', [ApiAddressController::class, 'index']);
        Route::post('/addresses', [ApiAddressController::class, 'store']);
        Route::get('/addresses/{address}', [ApiAddressController::class, 'show']);
        Route::put('/addresses/{address}', [ApiAddressController::class, 'update']);
        Route::patch('/addresses/{address}', [ApiAddressController::class, 'update']);
        Route::delete('/addresses/{address}', [ApiAddressController::class, 'destroy']);
        Route::patch('/addresses/{address}/set-default', [ApiAddressController::class, 'setDefault']);
    });
});
```

**Impacto:** 
- Las rutas funcionan pero usan autenticación web en lugar de API
- Esto es consistente con el enfoque híbrido del proyecto (Inertia.js)
- Frontend puede consumir estas rutas sin problemas

**Recomendación:** Mantener implementación actual ya que es coherente con la arquitectura del proyecto

## 📝 **Discrepancias Menores**

### 3. Tipos de Movimiento de Relojes

**📖 Documentación:** `'Automático', 'De Cuerda', 'Híbrido', 'Quartz'`

**⚙️ Factory:** `database/factories/ProductFactory.php`
```php
'movement_type' => $this->faker->randomElement(['Automatic', 'Manual', 'Quartz'])
```

**⚙️ Seeder:** `database/seeders/ProductSeeder.php`
```php
'movement_type' => 'Automático',
'movement_type' => 'Cuarzo',
```

**Inconsistencias:**
- Factory usa inglés: `'Automatic', 'Manual', 'Quartz'`
- Seeder usa español: `'Automático', 'Cuarzo'`
- Falta 'Híbrido' en implementaciones
- 'De Cuerda' vs 'Manual'
- 'Quartz' vs 'Cuarzo'

**Recomendación:** Estandarizar en español para consistencia con la UI

### 4. Campos de Imagen

**📖 Documentación:** `VARCHAR(2048)` para image_path
**⚙️ Implementación:** `VARCHAR(255)` en migraciones

**Archivos Afectados:**
- `database/migrations/2025_05_29_220120_create_categories_table.php`
- `database/migrations/2025_05_30_164215_create_products_table.php`

```php
$table->string('image_path')->nullable(); // VARCHAR(255) por defecto
```

**Impacto:** Limitación en URLs muy largas, pero 255 caracteres es generalmente suficiente

## 🎯 **Impacto en Desarrollo Futuro**

### Checklist Actualizado para Desarrolladores

Antes de implementar nuevos tickets:

- [ ] ✅ Usar campos `first_name` + `last_name` en lugar de `full_name`
- [ ] ✅ Usar `address_line_1` y `address_line_2` en lugar de `street_address` 
- [ ] ✅ Usar campo `type` + `is_default` en lugar de campos separados de shipping/billing
- [ ] ✅ Usar `country` completo en lugar de `country_code`
- [ ] ✅ Usar `phone` en lugar de `phone_number`
- [ ] ✅ Usar endpoint `/api/v1/user/addresses` para operaciones de direcciones
- [ ] ✅ Usar método `items()` para relación de cart items
- [ ] ⚠️ **NUEVO:** Considerar limitación de `DECIMAL(8, 2)` para precios de productos
- [ ] ⚠️ **NUEVO:** Usar autenticación web para rutas de direcciones (no Sanctum)
- [ ] ⚠️ **NUEVO:** Estandarizar tipos de movimiento en español

### Recomendaciones de Mejora

#### 1. Estandarizar Tipos de Movimiento
```php
// Actualizar ProductFactory.php
'movement_type' => $this->faker->randomElement(['Automático', 'De Cuerda', 'Híbrido', 'Cuarzo'])
```

#### 2. Considerar Migración de Precisión Decimal (Futuro)
Si se necesitan precios más altos:
```php
// Nueva migración futura
$table->decimal('price', 10, 2)->change();
```

#### 3. Documentar Arquitectura Híbrida
La implementación actual usa un enfoque híbrido:
- Rutas API para funcionalidad
- Autenticación web para seguridad
- Consistente con Inertia.js

## 📊 **Resumen de Estado**

### Discrepancias Resueltas ✅
- Modelo de direcciones actualizado
- Especificaciones de API actualizadas
- Documentación de modelo de datos corregida

### Discrepancias Identificadas ⚠️
- Precisión decimal en productos (8,2 vs 10,2)
- Rutas en web.php vs api.php
- Tipos de movimiento inconsistentes
- Longitud de campos de imagen

### Recomendación General
**Mantener implementación actual** - Las discrepancias encontradas son menores y la implementación actual es funcional y coherente con la arquitectura del proyecto.

---

**Nota:** Este archivo complementa `IMPLEMENTATION_NOTES.md` con hallazgos específicos de la revisión del backend. 
