#!/bin/bash
# docker-entrypoint.sh - Script de inicio para Moodle
# Maneja la instalación automática y configuración inicial

set -e

echo "=== Moodle Docker Entrypoint ==="

# Función para esperar a que la base de datos esté lista
wait_for_db() {
    echo "Esperando a que la base de datos esté disponible..."
    local max_attempts=30
    local attempt=0
    
    until nc -z -v -w5 "${MOODLE_DB_HOST:-db}" "${MOODLE_DB_PORT:-3306}" 2>/dev/null; do
        attempt=$((attempt + 1))
        if [ $attempt -ge $max_attempts ]; then
            echo "ERROR: No se pudo conectar a la base de datos después de $max_attempts intentos"
            exit 1
        fi
        echo "Intento $attempt/$max_attempts - Esperando base de datos..."
        sleep 2
    done
    
    echo "✓ Base de datos disponible"
    # Esperar 5 segundos adicionales para asegurar que MySQL está completamente listo
    sleep 5
}

# Función para configurar msmtp (SMTP)
configure_smtp() {
    if [ -n "$MOODLE_SMTP_HOST" ]; then
        echo "Configurando SMTP..."
        sed -i "s/%SMTP_HOST%/${MOODLE_SMTP_HOST}/g" /etc/msmtprc
        sed -i "s/%SMTP_PORT%/${MOODLE_SMTP_PORT:-587}/g" /etc/msmtprc
        sed -i "s/%SMTP_FROM%/${MOODLE_SMTP_FROM:-noreply@moodle.local}/g" /etc/msmtprc
        sed -i "s/%SMTP_USER%/${MOODLE_SMTP_USER}/g" /etc/msmtprc
        sed -i "s/%SMTP_PASS%/${MOODLE_SMTP_PASS}/g" /etc/msmtprc
        echo "✓ SMTP configurado"
    else
        echo "⚠ No se configuró SMTP (variables de entorno no definidas)"
    fi
}

# Función para instalar Moodle
install_moodle() {
    echo "Verificando instalación de Moodle..."
    
    # Verificar si Moodle ya está instalado
    if [ -f /var/www/moodledata/.moodle_installed ]; then
        echo "✓ Moodle ya está instalado"
        return 0
    fi
    
    # Verificar si las tablas de Moodle existen en la DB
    if php /var/www/html/admin/cli/check_database_schema.php 2>/dev/null; then
        echo "✓ Base de datos ya contiene tablas de Moodle"
        touch /var/www/moodledata/.moodle_installed
        return 0
    fi
    
    echo "Instalando Moodle..."
    echo "Esto puede tardar varios minutos..."
    
    # Variables de instalación
    local admin_user="${MOODLE_ADMIN_USER:-admin}"
    local admin_pass="${MOODLE_ADMIN_PASS:-Admin123!}"
    local admin_email="${MOODLE_ADMIN_EMAIL:-admin@example.com}"
    local site_name="${MOODLE_SITE_NAME:-Moodle Site}"
    local site_shortname="${MOODLE_SITE_SHORTNAME:-moodle}"
    local lang="${MOODLE_LANG:-es}"
    
    # Ejecutar instalación
    if php /var/www/html/admin/cli/install_database.php \
        --agree-license \
        --lang="$lang" \
        --adminuser="$admin_user" \
        --adminpass="$admin_pass" \
        --adminemail="$admin_email" \
        --fullname="$site_name" \
        --shortname="$site_shortname"; then
        
        echo "✓ Moodle instalado correctamente"
        touch /var/www/moodledata/.moodle_installed
        
        # Mostrar credenciales de administrador
        echo ""
        echo "=========================================="
        echo "  CREDENCIALES DE ADMINISTRADOR"
        echo "=========================================="
        echo "  Usuario: $admin_user"
        echo "  Contraseña: $admin_pass"
        echo "  Email: $admin_email"
        echo "=========================================="
        echo ""
    else
        echo "✗ Error al instalar Moodle"
        echo "Revisa los logs para más detalles"
        exit 1
    fi
}

# Función para ejecutar actualizaciones de base de datos
upgrade_moodle() {
    echo "Verificando actualizaciones de Moodle..."
    
    if php /var/www/html/admin/cli/upgrade.php --non-interactive; then
        echo "✓ Base de datos actualizada"
    else
        echo "⚠ No se pudo actualizar la base de datos (puede ser normal si no hay actualizaciones)"
    fi
}

# Función para iniciar cron
start_cron() {
    echo "Iniciando servicio cron..."
    
    # Asegurar que el archivo de log de cron existe
    touch /var/log/cron.log
    chown www-data:www-data /var/log/cron.log
    
    # Iniciar cron
    cron
    echo "✓ Cron iniciado"
}

# Función para ajustar permisos
fix_permissions() {
    echo "Verificando permisos..."
    
    # Asegurar que moodledata es escribible
    if [ ! -w /var/www/moodledata ]; then
        echo "Ajustando permisos de moodledata..."
        chown -R www-data:www-data /var/www/moodledata
        chmod -R 770 /var/www/moodledata
    fi
    
    # Asegurar que config.php es legible
    if [ -f /var/www/html/config.php ]; then
        chmod 644 /var/www/html/config.php
    fi
    
    echo "✓ Permisos verificados"
}

# ====== INICIO DEL SCRIPT ======

# 1. Configurar SMTP
configure_smtp

# 2. Ajustar permisos
fix_permissions

# 3. Esperar a que la base de datos esté lista
wait_for_db

# 4. Instalar Moodle si es necesario
install_moodle

# 5. Ejecutar actualizaciones si es necesario
upgrade_moodle

# 6. Iniciar cron
start_cron

# 7. Mostrar información del sistema
echo ""
echo "=========================================="
echo "  MOODLE DOCKER - LISTO"
echo "=========================================="
echo "  URL: ${MOODLE_URL:-http://localhost}"
echo "  Base de datos: ${MOODLE_DB_HOST:-db}"
echo "  Directorio de datos: ${MOODLE_DATA_DIR:-/var/www/moodledata}"
echo "=========================================="
echo ""

# 8. Ejecutar el comando principal (Apache)
echo "Iniciando Apache..."
exec "$@"
