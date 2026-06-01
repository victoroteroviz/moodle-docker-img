# Análisis Dockerfile Moodle 5.2 - Errores y Correcciones

## Fecha: 29 de mayo de 2026

## Resumen Ejecutivo
El Dockerfile tiene **varios errores críticos** que impedirán que Moodle funcione correctamente:
1. No ejecuta el script de instalación
2. No configura cron
3. Potenciales problemas de permisos

---

## Errores Críticos Encontrados

### 1. Script de Instalación NO se Ejecuta ❌
**Línea:** N/A (falta en el dockerfile)
**Problema:** El dockerfile crea config.php pero nunca ejecuta `admin/cli/install.php`
**Impacto:** Base de datos no inicializada, Moodle no funcional
**Solución sugerida:**
```dockerfile
# Agregar después de crear config.php:
RUN until nc -z -v -w30 db 3306; do echo "Esperando DB..."; sleep 1; done && \
    php /var/www/html/admin/cli/install_database.php \
        --agree-license \
        --adminuser=admin \
        --adminpass="${MOODLE_ADMIN_PASS:-Admin123!}" \
        --adminemail=admin@example.com \
        --fullname="Moodle Site" \
        --shortname=moodle
```

**Alternativa:** Usar un script de entrypoint que verifique si Moodle está instalado y lo instale en el primer arranque.

### 2. Falta Configuración de CRON ❌
**Línea:** N/A (falta en el dockerfile)
**Problema:** Cron no está configurado
**Impacto:** Tareas programadas, notificaciones, limpieza no funcionarán
**Solución sugerida:**
```dockerfile
# Instalar cron
RUN apt-get update && apt-get install -y cron && rm -rf /var/lib/apt/lists/*

# Crear entrada de cron
RUN echo "* * * * * www-data /usr/local/bin/php /var/www/html/admin/cli/cron.php >/dev/null 2>&1" > /etc/cron.d/moodle-cron \
    && chmod 0644 /etc/cron.d/moodle-cron \
    && crontab /etc/cron.d/moodle-cron

# Modificar CMD para iniciar cron y apache
CMD cron && apache2-foreground
```

### 3. Inconsistencia Versión Git ⚠️
**Línea:** `RUN curl -L https://github.com/moodle/moodle/archive/refs/heads/MOODLE_502_STABLE.tar.gz`
**Problema:** Documentación muestra MOODLE_501_STABLE (pero puede ser ejemplo viejo)
**Solución:** Verificar que MOODLE_502_STABLE es la rama correcta para Moodle 5.2

---

## Problemas de Lógica

### 4. Orden de Composer Install ⚠️
**Línea:** `RUN composer install --no-dev --classmap-authoritative --no-interaction --working-dir=/var/www/html`
**Problema:** Se ejecuta ANTES de crear config.php
**Solución:** Mover después de crear config.php, o verificar si es necesario antes

### 5. Permisos Restrictivos en Moodledata ⚠️
**Línea:** `RUN chmod 750 /var/www/moodledata`
**Problema:** chmod 750 puede ser demasiado restrictivo
**Solución:** Usar chmod 770 o verificar que www-data está en el grupo correcto

### 6. Config.php Ubicación 📝
**Línea:** `RUN cat <<'EOF' > /var/www/html/config.php`
**Problema:** Documentación menciona config.php en directorio base Y en public/
**Solución:** Clarificar si se necesitan ambos archivos

---

## Mejoras Recomendadas

### 7. Agregar Entrypoint Script
```dockerfile
# Crear script de inicio
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
```

**docker-entrypoint.sh:**
```bash
#!/bin/bash
set -e

# Esperar a que la DB esté disponible
until nc -z -v -w30 $MOODLE_DB_HOST 3306; do
    echo "Esperando base de datos..."
    sleep 2
done

# Instalar Moodle si no está instalado
if [ ! -f /var/www/moodledata/.moodle_installed ]; then
    echo "Instalando Moodle..."
    php /var/www/html/admin/cli/install_database.php \
        --agree-license \
        --adminuser=admin \
        --adminpass="${MOODLE_ADMIN_PASS}" \
        --adminemail="${MOODLE_ADMIN_EMAIL}" \
        --fullname="${MOODLE_SITE_NAME}" \
        --shortname=moodle
    
    touch /var/www/moodledata/.moodle_installed
    echo "Moodle instalado correctamente"
fi

# Iniciar cron en background
cron

# Ejecutar comando original
exec "$@"
```

### 8. Agregar Healthcheck
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s --retries=3 \
    CMD curl -f http://localhost/login/index.php || exit 1
```

### 9. Instalar MTA o Configurar SMTP
```dockerfile
# Opción 1: Instalar msmtp (ligero)
RUN apt-get update && apt-get install -y msmtp msmtp-mta && rm -rf /var/lib/apt/lists/*

# Opción 2: Configurar en config.php variables de entorno para SMTP
```

### 10. Variables de Entorno Faltantes
Agregar a docker-compose.yml:
```yaml
environment:
  - MOODLE_ADMIN_PASS=Admin123!
  - MOODLE_ADMIN_EMAIL=admin@example.com
  - MOODLE_SITE_NAME=Mi Sitio Moodle
  - MOODLE_SMTP_HOST=smtp.example.com
  - MOODLE_SMTP_PORT=587
```

---

## Checklist Documentación Oficial

Según [Installation quick guide](https://docs.moodle.org/502/en/Installation_quick_guide):

- [x] PHP 8.3+ instalado
- [x] Extensiones PHP requeridas
- [x] Apache configurado con mod_rewrite
- [x] Directorio public/ como DocumentRoot
- [x] Base de datos configurada (en docker-compose)
- [x] Directorio moodledata creado fuera de webroot
- [x] config.php con configuración correcta
- [ ] **Script de instalación ejecutado** ❌
- [ ] **Cron configurado** ❌
- [ ] **Mail configurado** ⚠️
- [x] Permisos correctos (parcial)

---

## Dockerfile Corregido (Propuesta)

Ver archivo `dockerfile.fixed` para una versión corregida con todas las mejoras implementadas.

---

## Prioridad de Correcciones

1. **ALTA:** Agregar script de instalación (vía entrypoint)
2. **ALTA:** Configurar cron
3. **MEDIA:** Corregir orden de composer install
4. **MEDIA:** Agregar healthcheck
5. **BAJA:** Configurar mail
6. **BAJA:** Ajustar permisos moodledata

---

## Referencias
- [Moodle 5.2 Installation Guide](https://docs.moodle.org/502/en/Installation_quick_guide)
- [Moodle Docker Best Practices](https://docs.moodle.org/en/Installation_using_Docker)
- [Cron Configuration](https://docs.moodle.org/502/en/Cron)
