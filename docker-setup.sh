#!/bin/bash

# Script de configuración para Docker - CronosMatic Store
echo "🚀 Configurando CronosMatic Store con Docker..."

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor, instala Docker primero."
    exit 1
fi

# Verificar si Docker Compose está instalado
if command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE_CMD="docker-compose"
elif docker compose version &> /dev/null; then
    DOCKER_COMPOSE_CMD="docker compose"
else
    echo "❌ Docker Compose no está instalado. Por favor, instala Docker Compose primero."
    exit 1
fi

echo "✅ Docker Compose detectado: $DOCKER_COMPOSE_CMD"

# Crear archivo .env si no existe
if [ ! -f .env ]; then
    echo "📝 Creando archivo .env..."
    cp docker.env .env
    echo "✅ Archivo .env creado. Por favor, edita las variables según tus necesidades."
else
    echo "ℹ️  El archivo .env ya existe."
fi

# Generar clave de aplicación si no está configurada
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generando clave de aplicación..."
    # La clave se generará cuando se ejecute el contenedor
    echo "ℹ️  La clave de aplicación se generará automáticamente al iniciar el contenedor."
fi

# Construir y levantar los contenedores
echo "🏗️  Construyendo contenedores..."
$DOCKER_COMPOSE_CMD build

echo "🚀 Iniciando servicios..."
$DOCKER_COMPOSE_CMD up -d

# Esperar a que los servicios estén listos
echo "⏳ Esperando a que los servicios estén listos..."
sleep 30

# Ejecutar migraciones y seeders
echo "🗄️  Ejecutando migraciones..."
$DOCKER_COMPOSE_CMD exec app php artisan migrate --force

echo "🌱 Ejecutando seeders..."
$DOCKER_COMPOSE_CMD exec app php artisan db:seed --force

echo "✅ ¡Configuración completada!"
echo ""
echo "📋 Información de acceso:"
echo "   🌐 Aplicación: http://localhost:8000"
echo "   🗄️  phpMyAdmin: http://localhost:8080"
echo "   📧 Usuario: cronosmatic"
echo "   🔑 Contraseña: cronosmatic_password"
echo ""
echo "🔧 Comandos útiles:"
echo "   Ver logs: $DOCKER_COMPOSE_CMD logs -f"
echo "   Detener servicios: $DOCKER_COMPOSE_CMD down"
echo "   Reiniciar servicios: $DOCKER_COMPOSE_CMD restart"
echo "   Acceder al contenedor: $DOCKER_COMPOSE_CMD exec app bash"
echo ""
echo "🎉 ¡CronosMatic Store está listo para usar!"
