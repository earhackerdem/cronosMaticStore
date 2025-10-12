# Docker Setup - CronosMaticStore

## Resumen del Entorno

Este proyecto utiliza Docker para proporcionar un entorno de desarrollo completo con los siguientes servicios:

- **Laravel 12** con PHP 8.2
- **MariaDB** como base de datos
- **Redis** para caché y sesiones
- **Nginx** como servidor web
- **Vite** para desarrollo frontend con hot reload
- **phpMyAdmin** para gestión de base de datos

## Servicios Disponibles

### Entorno de Desarrollo (`dev`)
- **URL**: http://localhost:3000 (Laravel)
- **Vite**: http://localhost:5173 (Hot reload)
- **Puerto**: 3000, 5173
- **Características**: Hot reload, dependencias de desarrollo, debugging

### Entorno de Producción (`app`)
- **URL**: http://localhost:8000
- **Puerto**: 8000
- **Características**: Optimizado para producción

### Base de Datos (`db`)
- **Tipo**: MariaDB 10.11
- **Puerto**: 3306
- **Credenciales**:
  - Usuario: `cronosmatic`
  - Contraseña: `cronosmatic_password`
  - Base de datos: `cronosmatic`

### Redis (`redis`)
- **Puerto**: 6379
- **Uso**: Caché y sesiones de Laravel

### phpMyAdmin (`phpmyadmin`)
- **URL**: http://localhost:8080
- **Credenciales**:
  - Usuario: `cronosmatic`
  - Contraseña: `cronosmatic_password`

## Comandos Principales

### Iniciar el entorno de desarrollo
```bash
./docker-setup.sh dev
```

### Iniciar el entorno de desarrollo completo (con phpMyAdmin)
```bash
docker compose up -d dev phpmyadmin
```

### Iniciar el entorno de producción
```bash
./docker-setup.sh prod
```

### Iniciar solo phpMyAdmin
```bash
docker compose up -d phpmyadmin
```

### Detener todos los servicios
```bash
docker compose down
```

### Ver logs del entorno de desarrollo
```bash
docker compose logs -f dev
```

### Acceder al contenedor de desarrollo
```bash
docker compose exec dev bash
```

### Ejecutar comandos de Laravel
```bash
docker compose exec dev php artisan migrate
docker compose exec dev php artisan db:seed
docker compose exec dev php artisan tinker
```

### Ejecutar tests
```bash
docker compose exec dev php artisan test
docker compose exec dev npm test
```

## Problemas Resueltos

### ✅ Error "Class Redis not found"
**Problema**: Laravel no podía encontrar la clase Redis.
**Solución**: Instalación de la extensión PHP Redis vía PECL en el Dockerfile.

### ✅ Warnings de extensiones PHP duplicadas
**Problema**: Extensiones PHP cargadas múltiples veces causando warnings.
**Solución**: Eliminación de las líneas de extensión duplicadas del archivo `custom.ini`.

### ✅ Errores de seeders por duplicados
**Problema**: Los seeders fallaban por datos duplicados en la base de datos.
**Solución**: El script de inicio maneja estos errores de forma elegante.

## Configuración de Archivos

### Variables de Entorno
El archivo `docker.env` contiene todas las variables necesarias para el entorno Docker.

### Configuración PHP
- **Archivo**: `docker/php/custom.ini`
- **Características**: Configuración optimizada para desarrollo con límites de memoria y tiempo aumentados.

### Configuración Nginx
- **Archivo**: `docker/nginx.conf`
- **Características**: Configuración optimizada para Laravel con soporte para archivos estáticos.

## Desarrollo

### Hot Reload
El entorno de desarrollo incluye:
- **Vite**: Hot reload para archivos JavaScript/TypeScript
- **Laravel**: Servidor de desarrollo con recarga automática

### Base de Datos
- Las migraciones se ejecutan automáticamente al iniciar el entorno
- Los seeders se ejecutan con manejo de errores de duplicados
- Datos de prueba incluidos automáticamente

### Logs
Los logs están disponibles en:
- **Laravel**: `storage/logs/`
- **Nginx**: `/var/log/nginx/`
- **PHP**: `/var/log/php_errors.log`

## Troubleshooting

### Si el contenedor no inicia
1. Verificar que Docker esté ejecutándose
2. Verificar que los puertos no estén en uso
3. Ejecutar `docker compose down` y luego `docker compose up -d dev`

### Si hay problemas de permisos
```bash
docker compose exec dev chown -R www-data:www-data /var/www/html
```

### Si hay problemas de dependencias
```bash
docker compose exec dev composer install
docker compose exec dev npm install
```

### Si hay problemas de base de datos
```bash
docker compose exec dev php artisan migrate:fresh --seed
```

## Notas Importantes

- El entorno de desarrollo incluye todas las dependencias de desarrollo
- Los archivos del proyecto están montados como volúmenes para hot reload
- La base de datos persiste entre reinicios del contenedor
- Redis está configurado para caché y sesiones de Laravel
- El entorno está optimizado para desarrollo con debugging habilitado
