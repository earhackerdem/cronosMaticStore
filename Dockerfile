# Dockerfile para CronosMaticStore
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    nginx \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && echo 'no' | pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 18 (versión específica desde NodeSource)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Instalar dependencias de Cypress para tests E2E (opcional para producción)
RUN apt-get update && apt-get install -y \
    xvfb \
    libgtk2.0-0 \
    libgtk-3-0 \
    libgbm-dev \
    libnotify-dev \
    libnss3 \
    libxss1 \
    libasound2 \
    libxtst6 \
    xauth \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de dependencias
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Instalar dependencias de PHP (sin scripts post-install)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Instalar dependencias de Node.js
RUN npm ci --only=production

# Copiar el código de la aplicación
COPY . .

# Ejecutar scripts post-install después de copiar el código
RUN composer run-script post-autoload-dump

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Construir assets de producción
RUN npm run build

# Configurar PHP
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
