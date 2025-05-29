## Requisitos previos
- PHP 8.2 o superior
- Composer
- Node.js (versión reciente)
- npm

## Pasos para la instalación

### 1. Clonar el repositorio
```
git clone https://github.com/earhackerdem/cronosMaticStore
cd cronosMaticStore
```

### 2. Instalar dependencias de PHP
```
composer install
```

### 3. Instalar dependencias de JavaScript
```
npm install
```

### 4. Configurar el entorno
```
cp .env.example .env
```

### 5. Configurar SQLite
Edita el archivo `.env` para usar SQLite:
```
DB_CONNECTION=sqlite
DB_DATABASE=/Users/sehnhack/Documents/projects/cronosMaticStore/database/database.sqlite
```

### 6. Crear el archivo de base de datos SQLite
```
touch database/database.sqlite
```

### 7. Generar la clave de la aplicación
```
php artisan key:generate
```

### 8. Ejecutar las migraciones
```
php artisan migrate
```

### 9. Configurar base de datos de pruebas
Para ejecutar las pruebas, se utiliza una base de datos SQLite separada. El archivo de la base de datos de pruebas se creará automáticamente al ejecutar los tests o puede crearlo manualmente:
```
touch database/testing.sqlite
```
Asegúrate de que tu archivo `.env.testing` (si lo tienes) o tu configuración de `phpunit.xml` apunten a `database/testing.sqlite`.

### 10. Opcional: Cargar datos de prueba
```
php artisan db:seed
```

### 11. Compilar assets
```
npm run build
```

### 12. Iniciar el servidor
```
php artisan serve
```

Ahora puedes acceder a la aplicación en http://localhost:8000

## Desarrollo

Para trabajar en modo desarrollo con hot reload:
```
npm run dev
```

En otra terminal, inicia el servidor de Laravel:
```
php artisan serve
```

## Comando todo en uno para desarrollo
También puedes usar el comando definido en composer.json:
```
composer run dev
```

Esto iniciará concurrentemente el servidor Laravel, la cola de trabajos, los logs y Vite en modo desarrollo.

## Workflows de GitHub Actions

Este proyecto utiliza GitHub Actions para automatizar ciertas tareas:

### Tests

El workflow `tests.yml` se ejecuta en cada push y pull request a las ramas `develop` y `main`. Este workflow se encarga de:
- Configurar PHP y Node.js.
- Instalar dependencias de Composer y npm.
- Compilar los assets.
- Crear una base de datos SQLite para pruebas en `database/testing.sqlite`.
- Generar la clave de la aplicación.
- Ejecutar las pruebas con PHPUnit.

### Linter

El workflow `lint.yml` se ejecuta en cada push y pull request a las ramas `develop` y `main`. Este workflow se encarga de:
- Configurar PHP.
- Instalar dependencias de Composer y npm.
- Ejecutar Pint para formatear el código PHP.
- Ejecutar `npm run format` para formatear el código frontend.
- Ejecutar `npm run lint` para verificar el estilo del código frontend.
