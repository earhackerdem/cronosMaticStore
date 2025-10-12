#!/bin/bash

# Script de configuración para Docker - CronosMatic Store
echo "🚀 Configurando CronosMatic Store con Docker..."

# Función de ayuda
show_help() {
    echo "Uso: $0 [comando]"
    echo ""
    echo "Comandos disponibles:"
    echo "  prod         - Iniciar entorno de producción (puerto 8000)"
    echo "  dev          - Iniciar entorno de desarrollo (puerto 3000)"
    echo "  dev-full     - Iniciar entorno de desarrollo con phpMyAdmin"
    echo "  phpmyadmin   - Iniciar solo phpMyAdmin"
    echo "  down         - Detener todos los servicios"
    echo "  help         - Mostrar esta ayuda"
    echo ""
    echo "Si no se especifica comando, se iniciará el entorno de producción."
}

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

# Procesar comando
COMMAND=${1:-prod}

case $COMMAND in
    help)
        show_help
        exit 0
        ;;
    down)
        echo "🛑 Deteniendo todos los servicios..."
        $DOCKER_COMPOSE_CMD down
        echo "✅ Servicios detenidos."
        exit 0
        ;;
    phpmyadmin)
        echo "🗄️  Iniciando phpMyAdmin..."
        $DOCKER_COMPOSE_CMD up -d phpmyadmin
        echo "✅ phpMyAdmin iniciado en http://localhost:8080"
        exit 0
        ;;
esac

# Crear archivo .env si no existe
if [ ! -f .env ]; then
    echo "📝 Creando archivo .env..."
    cp .env.example .env
    echo "✅ Archivo .env creado. Por favor, edita las variables según tus necesidades."
else
    echo "ℹ️  El archivo .env ya existe."
fi

# Generar clave de aplicación si no está configurada
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generando clave de aplicación..."
    echo "ℹ️  La clave de aplicación se generará automáticamente al iniciar el contenedor."
fi

# Construir y levantar los contenedores según el comando
case $COMMAND in
    dev)
        echo "🏗️  Construyendo contenedor de desarrollo..."
        $DOCKER_COMPOSE_CMD build dev
        echo "🚀 Iniciando entorno de desarrollo..."
        $DOCKER_COMPOSE_CMD up -d dev
        MAIN_URL="http://localhost:3000"
        VITE_URL="http://localhost:5173"
        CONTAINER_NAME="dev"
        ;;
    dev-full)
        echo "🏗️  Construyendo contenedor de desarrollo..."
        $DOCKER_COMPOSE_CMD build dev
        echo "🚀 Iniciando entorno de desarrollo completo..."
        $DOCKER_COMPOSE_CMD up -d dev phpmyadmin
        MAIN_URL="http://localhost:3000"
        VITE_URL="http://localhost:5173"
        CONTAINER_NAME="dev"
        ;;
    prod)
        echo "🏗️  Construyendo contenedor de producción..."
        $DOCKER_COMPOSE_CMD build app
        echo "🚀 Iniciando entorno de producción..."
        $DOCKER_COMPOSE_CMD up -d app phpmyadmin
        MAIN_URL="http://localhost:8000"
        CONTAINER_NAME="app"
        ;;
    *)
        echo "❌ Comando no reconocido: $COMMAND"
        show_help
        exit 1
        ;;
esac

# Esperar a que los servicios estén listos
echo "⏳ Esperando a que los servicios estén listos..."
sleep 15

echo "✅ ¡Configuración completada!"
echo ""
echo "📋 Información de acceso:"
echo "   🌐 Aplicación: $MAIN_URL"
if [ "$COMMAND" = "dev" ] || [ "$COMMAND" = "dev-full" ]; then
    echo "   ⚡ Vite (Hot Reload): $VITE_URL"
fi
if [ "$COMMAND" = "dev-full" ] || [ "$COMMAND" = "prod" ]; then
    echo "   🗄️  phpMyAdmin: http://localhost:8080"
fi
echo "   📧 Usuario DB: cronosmatic"
echo "   🔑 Contraseña DB: cronosmatic_password"
echo ""
echo "🔧 Comandos útiles:"
echo "   Ver logs: $DOCKER_COMPOSE_CMD logs -f $CONTAINER_NAME"
echo "   Detener servicios: $DOCKER_COMPOSE_CMD down"
echo "   Reiniciar servicios: $DOCKER_COMPOSE_CMD restart"
echo "   Acceder al contenedor: $DOCKER_COMPOSE_CMD exec $CONTAINER_NAME bash"
echo ""
echo "🎉 ¡CronosMatic Store está listo para usar!"
