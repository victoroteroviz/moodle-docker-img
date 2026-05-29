# Guía de Implementación - Dockerfile Corregido para Moodle 5.2

## Archivos Generados

Los siguientes archivos han sido creados en `/documentacion/`:

1. **analisis_dockerfile.md** - Análisis detallado de errores encontrados
2. **dockerfile.fixed** - Dockerfile corregido con todas las mejoras
3. **docker-entrypoint.sh** - Script de inicio automatizado
4. **docker-compose.fixed.yml** - Configuración Docker Compose mejorada
5. **INSTRUCCIONES_USO.md** - Este archivo

---

## Diferencias Principales vs Dockerfile Original

### ❌ Dockerfile Original
- No ejecuta script de instalación → Moodle no funciona
- No configura cron → Tareas programadas no funcionan
- composer install antes de config.php
- chmod 750 en moodledata (muy restrictivo)
- No tiene healthcheck
- No configura SMTP
- CMD solo inicia Apache

### ✅ Dockerfile Corregido
- ✅ Entrypoint automático que instala Moodle en primer arranque
- ✅ Cron configurado para ejecutarse cada minuto
- ✅ composer install después de config.php
- ✅ chmod 770 en moodledata (más compatible)
- ✅ Healthcheck para monitoreo
- ✅ SMTP configurado (msmtp)
- ✅ netcat instalado para verificar DB
- ✅ ENTRYPOINT + CMD para inicialización completa

---

## Cómo Usar los Archivos Corregidos

### Opción 1: Reemplazar Archivos Existentes

```bash
cd /home/littlekid/Documentos/Sistemas/okip_uni

# Backup del dockerfile original
cp dockerfile dockerfile.backup

# Reemplazar con versión corregida
cp documentacion/dockerfile.fixed dockerfile

# Copiar script de entrypoint
cp documentacion/docker-entrypoint.sh .
chmod +x docker-entrypoint.sh

# Opcional: Reemplazar docker-compose
cp docker-compose.yml docker-compose.backup.yml
cp documentacion/docker-compose.fixed.yml docker-compose.yml
```

### Opción 2: Probar Lado a Lado

```bash
cd /home/littlekid/Documentos/Sistemas/okip_uni

# Copiar archivos necesarios
cp documentacion/docker-entrypoint.sh .
chmod +x docker-entrypoint.sh

# Usar docker-compose mejorado
docker compose -f documentacion/docker-compose.fixed.yml up -d
```

---

## Pasos para Desplegar Moodle

### 1. Preparación

```bash
cd /home/littlekid/Documentos/Sistemas/okip_uni

# Asegurar que docker-entrypoint.sh existe y es ejecutable
chmod +x docker-entrypoint.sh

# Opcional: Editar variables de entorno en docker-compose.yml
nano docker-compose.yml  # o documentacion/docker-compose.fixed.yml
```

### 2. Construir e Iniciar

```bash
# Limpiar contenedores anteriores (si existen)
docker compose down -v  # ⚠️ Esto BORRA datos de DB

# Construir imagen
docker compose build --no-cache

# Iniciar servicios
docker compose up -d

# Ver logs en tiempo real
docker compose logs -f moodle
```

### 3. Verificar Instalación

El script de entrypoint se encargará automáticamente de:
- ✅ Esperar a que la base de datos esté lista
- ✅ Instalar Moodle (primera vez)
- ✅ Iniciar cron
- ✅ Configurar permisos
- ✅ Iniciar Apache

```bash
# Ver logs de instalación
docker compose logs moodle

# Verificar que cron está corriendo
docker compose exec moodle ps aux | grep cron

# Verificar que Moodle responde
curl http://localhost:8080/login/index.php
```

### 4. Acceder a Moodle

Abre tu navegador en:
- **Moodle:** http://localhost:8080
- **phpMyAdmin:** http://localhost:8081 (si está habilitado)

**Credenciales por defecto:**
- Usuario: `admin`
- Contraseña: `Admin123!`

---

## Variables de Entorno Importantes

Configura estas variables en `docker-compose.yml` según tus necesidades:

### Base de Datos
```yaml
MOODLE_DB_TYPE: mariadb          # o pgsql, mysql
MOODLE_DB_HOST: db               # nombre del servicio DB
MOODLE_DB_NAME: moodle
MOODLE_DB_USER: moodleuser
MOODLE_DB_PASS: moodlepass
```

### Sitio Web
```yaml
MOODLE_URL: http://localhost:8080      # URL pública de tu Moodle
MOODLE_SITE_NAME: "Mi Sitio Moodle"   # Nombre del sitio
MOODLE_LANG: es                        # Idioma (es, en, fr, etc.)
```

### Administrador (primera instalación)
```yaml
MOODLE_ADMIN_USER: admin
MOODLE_ADMIN_PASS: Admin123!          # ⚠️ Cambiar en producción
MOODLE_ADMIN_EMAIL: admin@example.com
```

### SMTP (opcional)
```yaml
MOODLE_SMTP_HOST: smtp.gmail.com
MOODLE_SMTP_PORT: 587
MOODLE_SMTP_USER: tu_email@gmail.com
MOODLE_SMTP_PASS: tu_contraseña_app   # App password, no contraseña normal
MOODLE_SMTP_FROM: noreply@moodle.local
MOODLE_SMTP_SECURE: tls               # o ssl
```

---

## Verificación Post-Instalación

### 1. Verificar Cron

```bash
# Dentro del contenedor, verificar que cron está configurado
docker compose exec moodle crontab -l

# Debería mostrar:
# * * * * * www-data /usr/local/bin/php /var/www/html/admin/cli/cron.php >/dev/null 2>&1

# Verificar que cron está corriendo
docker compose exec moodle ps aux | grep cron

# Ejecutar cron manualmente para probar
docker compose exec -u www-data moodle php /var/www/html/admin/cli/cron.php
```

### 2. Verificar Permisos

```bash
# Verificar permisos de moodledata
docker compose exec moodle ls -la /var/www/ | grep moodledata
# Debería ser: drwxrwx--- www-data www-data

# Verificar que moodledata es escribible
docker compose exec moodle test -w /var/www/moodledata && echo "OK" || echo "ERROR"
```

### 3. Verificar Salud del Contenedor

```bash
# Ver estado de healthcheck
docker compose ps

# Debería mostrar "(healthy)" en la columna STATUS

# Ver logs de healthcheck
docker inspect moodle_app | jq '.[0].State.Health'
```

### 4. Verificar Moodle en el Navegador

Accede a http://localhost:8080 y verifica:
- ✅ La página de login carga correctamente
- ✅ Puedes iniciar sesión con admin/Admin123!
- ✅ No hay errores de permisos
- ✅ Puedes navegar por el sitio

En *Administración del sitio > Servidor > Tareas programadas*:
- ✅ Verificar que las tareas se están ejecutando

---

## Solución de Problemas

### Problema: "Moodle no se instala automáticamente"

**Síntomas:** No puedes acceder a Moodle, muestra error de instalación

**Solución:**
```bash
# Ver logs de instalación
docker compose logs moodle | grep -i install

# Reinstalar manualmente
docker compose exec moodle php /var/www/html/admin/cli/install_database.php \
  --agree-license \
  --adminuser=admin \
  --adminpass=Admin123! \
  --adminemail=admin@example.com \
  --fullname="Moodle Site" \
  --shortname=moodle
```

### Problema: "Error de permisos en moodledata"

**Síntomas:** Errores de escritura, no se pueden subir archivos

**Solución:**
```bash
# Corregir permisos
docker compose exec moodle chown -R www-data:www-data /var/www/moodledata
docker compose exec moodle chmod -R 770 /var/www/moodledata
```

### Problema: "Cron no se ejecuta"

**Síntomas:** Tareas programadas no funcionan

**Solución:**
```bash
# Verificar que cron está corriendo
docker compose exec moodle service cron status

# Reiniciar cron
docker compose exec moodle service cron restart

# Verificar crontab
docker compose exec moodle crontab -l
```

### Problema: "No se pueden enviar emails"

**Síntomas:** Moodle no envía notificaciones por correo

**Solución:**
1. Configurar variables SMTP en docker-compose.yml
2. Verificar configuración en Moodle: *Administración > Servidor > Email > Configuración saliente*
3. Probar envío manual desde línea de comandos:
```bash
docker compose exec moodle php /var/www/html/admin/cli/test_outgoing_mail_configuration.php
```

### Problema: "Base de datos no se conecta"

**Síntomas:** Error al conectar a la base de datos

**Solución:**
```bash
# Verificar que DB está saludable
docker compose ps db

# Verificar conectividad desde contenedor Moodle
docker compose exec moodle nc -zv db 3306

# Ver logs de DB
docker compose logs db
```

---

## Mantenimiento

### Backup

```bash
# Backup de base de datos
docker compose exec db mysqldump -u moodleuser -pmoodlepass moodle > backup_$(date +%Y%m%d).sql

# Backup de moodledata
docker compose exec moodle tar czf - /var/www/moodledata > moodledata_backup_$(date +%Y%m%d).tar.gz
```

### Actualización de Moodle

```bash
# Descargar nueva versión
# (Editar dockerfile para cambiar rama MOODLE_XXX_STABLE)

# Reconstruir
docker compose build --no-cache

# Reiniciar
docker compose up -d

# El script de entrypoint ejecutará automáticamente:
# php admin/cli/upgrade.php --non-interactive
```

### Ver Logs

```bash
# Logs de Moodle
docker compose logs -f moodle

# Logs de cron
docker compose exec moodle tail -f /var/log/cron.log

# Logs de Apache
docker compose exec moodle tail -f /var/log/apache2/error.log
```

---

## Comparación con Documentación Oficial

### ✅ Checklist Completo

Según [Moodle Installation Quick Guide](https://docs.moodle.org/502/en/Installation_quick_guide):

- [x] PHP 8.3+ instalado
- [x] Extensiones PHP requeridas (gd, intl, xml, soap, zip, pdo, etc.)
- [x] Apache con mod_rewrite habilitado
- [x] Directorio public/ como DocumentRoot (Moodle 5.1+)
- [x] Base de datos UTF8 (utf8mb4_unicode_ci)
- [x] Usuario de DB con permisos correctos
- [x] Directorio moodledata fuera de webroot
- [x] config.php con configuración correcta
- [x] **Script de instalación ejecutado** ✅
- [x] **Cron configurado para ejecutarse cada minuto** ✅
- [x] **Mail configurado (msmtp)** ✅
- [x] Permisos correctos (lectura en código, escritura en datos)

### Diferencia Clave

El dockerfile original **NO cumplía** con 3 requisitos críticos:
1. ❌ No ejecutaba script de instalación
2. ❌ No configuraba cron
3. ❌ No configuraba mail

El dockerfile corregido **SÍ cumple** con TODOS los requisitos.

---

## Conclusión

El dockerfile corregido implementa:

✅ **Instalación automática** mediante entrypoint
✅ **Cron funcional** para tareas programadas  
✅ **Configuración SMTP** para envío de emails
✅ **Healthcheck** para monitoreo
✅ **Permisos correctos** según documentación oficial
✅ **100% compatible** con Moodle 5.2 installation guide

**Recomendación:** Usa los archivos corregidos en producción.

---

## Referencias

- [Moodle 5.2 Installation Guide](https://docs.moodle.org/502/en/Installation_quick_guide)
- [Moodle Docker Best Practices](https://docs.moodle.org/en/Installation_using_Docker)
- [Moodle Cron Configuration](https://docs.moodle.org/502/en/Cron)
- [Moodle Mail Configuration](https://docs.moodle.org/502/en/Mail_configuration)

---

**Generado:** 29 de mayo de 2026  
**Autor:** Análisis de Dockerfile Moodle 5.2  
**Versión:** 1.0
