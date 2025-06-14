# Railway Deployment Guide

## Variables de Entorno Necesarias

Para solucionar el problema de Mixed Content con HTTPS en Railway, configura estas variables de entorno:

### Variables Básicas de Laravel
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:tu-app-key-generada
APP_URL=https://cronosmaticstore-production.up.railway.app
ASSET_URL=https://cronosmaticstore-production.up.railway.app
APP_FORCE_HTTPS=true
```

### Base de Datos
```env
DATABASE_URL=${{Postgres.DATABASE_URL}}
```

### Seguridad y Sesión
```env
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE_COOKIE=none
```

### Otras Configuraciones
```env
MAIL_MAILER=log
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### PayPal (opcional - ya configurado)
```env
PAYPAL_CLIENT_ID=tu-paypal-client-id
PAYPAL_CLIENT_SECRET=tu-paypal-client-secret
```

## Comando de Build Recomendado

En Railway Settings → Build Command:
```bash
composer install && npm ci && npm run build && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan migrate:fresh --seed --force && composer install --no-dev --optimize-autoloader && php artisan config:cache && chmod -R 777 storage && php artisan storage:link
```

## Problemas Solucionados

- ✅ Mixed Content Error (HTTP assets en página HTTPS)
- ✅ Assets no cargan correctamente
- ✅ Pantalla negra por CSS/JS bloqueados
- ✅ URLs generadas con protocolo incorrecto

## Verificación

Después de configurar las variables y desplegar:
1. Los assets deberían cargar con HTTPS
2. No deberían aparecer errores de Mixed Content en consola
3. La aplicación debería cargar correctamente 
