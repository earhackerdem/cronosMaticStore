#!/bin/bash

# Script de inicio para entorno de desarrollo
echo "🚀 Iniciando entorno de desarrollo..."

# Función para esperar a que la base de datos esté disponible
wait_for_db() {
    echo "⏳ Esperando a que la base de datos esté disponible..."

    # Intentar conectar hasta 30 veces (30 segundos)
    for i in {1..30}; do
        if php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'DB_READY'; } catch (Exception \$e) { echo 'DB_NOT_READY'; }" 2>/dev/null | grep -q "DB_READY"; then
            echo "✅ Base de datos disponible!"
            return 0
        fi
        echo "⏳ Intento $i/30 - Esperando base de datos..."
        sleep 1
    done

    echo "❌ Error: No se pudo conectar a la base de datos después de 30 segundos"
    exit 1
}

# Función para limpiar procesos anteriores
cleanup_processes() {
    echo "🧹 Limpiando procesos anteriores..."

    # Matar procesos de Vite y Laravel que puedan estar ejecutándose
    pkill -f "vite" 2>/dev/null || true
    pkill -f "php artisan serve" 2>/dev/null || true
    pkill -f "node.*vite" 2>/dev/null || true

    # Esperar un momento para que los procesos terminen
    sleep 2

    echo "✅ Procesos limpiados"
}

# Función para manejar señales de terminación
cleanup() {
    echo "🛑 Cerrando servicios..."
    kill -TERM $VITE_PID $LARAVEL_PID 2>/dev/null
    wait
    exit 0
}

# Configurar trap para manejar señales
trap cleanup SIGTERM SIGINT

# Limpiar procesos anteriores
cleanup_processes

# Instalar dependencias de PHP si no existen
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependencias de PHP..."
    composer install
fi

# Instalar dependencias de Node.js si no existen
if [ ! -d "node_modules" ]; then
    echo "📦 Instalando dependencias de Node.js..."
    npm install
fi

# Generar clave de aplicación si no existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generando clave de aplicación..."
    php artisan key:generate --force
fi

# Esperar a que la base de datos esté disponible
wait_for_db

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders (ignorar errores de duplicados)
echo "🌱 Ejecutando seeders..."
php artisan db:seed --force || echo "⚠️  Algunos seeders fallaron (posiblemente datos duplicados)"

# Iniciar servidor de desarrollo
echo "🎯 Iniciando servidor de desarrollo..."

# Ejecutar Vite en segundo plano con puerto específico
echo "🔥 Iniciando Vite en puerto 5173..."
npm run dev -- --port 5173 --host 0.0.0.0 &
VITE_PID=$!

# Esperar un momento para que Vite se inicie
sleep 5

# Verificar que Vite esté funcionando
echo "🔍 Verificando que Vite esté funcionando..."
for i in {1..10}; do
    if curl -s http://localhost:5173/ >/dev/null 2>&1; then
        echo "✅ Vite está funcionando en puerto 5173"
        break
    fi
    echo "⏳ Esperando Vite... intento $i/10"
    sleep 1
done

# Ejecutar servidor de Laravel en segundo plano
echo "🚀 Iniciando Laravel..."
php artisan serve --host=0.0.0.0 --port=3000 &
LARAVEL_PID=$!

# Esperar a que ambos procesos terminen
echo "✅ Servicios iniciados. Presiona Ctrl+C para detener."
wait
