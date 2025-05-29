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
DB_DATABASE=database/database.sqlite
```

### 6. Crear el archivo de base de datos SQLite
```
touch database/database.sqlite
```

### 7. Generar la clave de la aplicación