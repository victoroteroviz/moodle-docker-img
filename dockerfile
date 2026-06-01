# Dockerfile corregido para Moodle 5.2
# Incluye correcciones de errores críticos identificados en el análisis

FROM php:8.3-apache

# Composer para dependencias PHP
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Configuración de Apache para servir desde public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Habilitar mod_rewrite para URLs limpias
RUN a2enmod rewrite

# Ajustar DocumentRoot a public/
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar .htaccess en public/
RUN cat <<'EOF' > /etc/apache2/conf-available/moodle-public.conf
<Directory /var/www/html/public>
    AllowOverride All
    Require all granted
</Directory>
EOF

RUN a2enconf moodle-public

# Configuración PHP recomendada por Moodle
RUN cat <<'EOF' > /usr/local/etc/php/conf.d/moodle-recommended.ini
zend.exception_ignore_args=1
max_input_vars=5000
upload_max_filesize=100M
post_max_size=100M
memory_limit=256M
max_execution_time=300
EOF

# Instalar dependencias del sistema
# CORRECCIÓN: Agregar cron y netcat para healthcheck
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libsodium-dev \
    curl \
    git \
    unzip \
    cron \
    netcat-openbsd \
    msmtp \
    msmtp-mta \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install gd intl xml soap zip mysqli pdo pdo_mysql pdo_pgsql opcache sodium mbstring exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Descargar Moodle 5.2 (MOODLE_502_STABLE)
RUN curl -L https://download.moodle.org/download.php/direct/stable502/moodle-5.2.tgz \
    | tar xz -C /var/www/html --strip-components=1 --no-same-owner \
    && chown -R www-data:www-data /var/www/html

# Crear directorios de datos con permisos correctos
# CORRECCIÓN: chmod 770 en lugar de 750 para mejor compatibilidad
RUN mkdir -p /var/www/moodledata /var/www/moodledata/localcache \
    && chown -R www-data:www-data /var/www/moodledata \
    && chmod 770 /var/www/moodledata

# Crear config.php con configuración dinámica
RUN cat <<'EOF' > /var/www/html/config.php
<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

// Configuración de Base de Datos
$CFG->dbtype    = getenv('MOODLE_DB_TYPE') ?: 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST') ?: 'db';
$CFG->dbname    = getenv('MOODLE_DB_NAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER') ?: 'moodleuser';
$CFG->dbpass    = getenv('MOODLE_DB_PASS') ?: 'moodlepass';
$CFG->prefix    = getenv('MOODLE_DB_PREFIX') ?: 'mdl_';

// Configuración de Rutas
$CFG->wwwroot   = getenv('MOODLE_URL') ?: 'http://localhost';
$CFG->dataroot  = getenv('MOODLE_DATA_DIR') ?: '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02777;

// SSL Proxy
if (getenv('MOODLE_REVERSE_PROXY') === 'true') {
    $CFG->sslproxy = true;
}

// Router Configuration
$CFG->pathtophp = '/usr/local/bin/php';
$CFG->localcachedir = '/var/www/moodledata/localcache';

// SMTP Configuration (opcional)
if (getenv('MOODLE_SMTP_HOST')) {
    $CFG->smtphosts = getenv('MOODLE_SMTP_HOST') . ':' . (getenv('MOODLE_SMTP_PORT') ?: '587');
    $CFG->smtpuser = getenv('MOODLE_SMTP_USER') ?: '';
    $CFG->smtppass = getenv('MOODLE_SMTP_PASS') ?: '';
    $CFG->smtpsecure = getenv('MOODLE_SMTP_SECURE') ?: 'tls';
}

// Modo desarrollo. No usar en producción.
if (getenv('MOODLE_DEV_MODE') === 'true') {
    @error_reporting(E_ALL | E_STRICT);
    @ini_set('display_errors', '1');

    $CFG->debug = E_ALL | E_STRICT;
    $CFG->debugdisplay = true;

    // Recompila SCSS y evita cachés agresivos durante desarrollo.
    $CFG->themedesignermode = true;
    $CFG->cachejs = false;
    $CFG->cachetemplates = false;
    $CFG->langstringcache = false;
}

require_once(__DIR__ . '/lib/setup.php');
EOF

# CORRECCIÓN: Composer install DESPUÉS de config.php con optimización completa
RUN composer install --no-dev --classmap-authoritative --no-interaction --working-dir=/var/www/html

# Ajustar permisos finales
RUN chown -R www-data:www-data /var/www/html \
    && chmod 644 /var/www/html/config.php \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \;

# CORRECCIÓN: Configurar cron para ejecutar tareas de Moodle cada minuto
RUN echo "* * * * * www-data /usr/local/bin/php /var/www/html/admin/cli/cron.php >/dev/null 2>&1" > /etc/cron.d/moodle-cron \
    && chmod 0644 /etc/cron.d/moodle-cron \
    && crontab /etc/cron.d/moodle-cron

# Configuración básica de msmtp (MTA ligero)
RUN cat <<'EOF' > /etc/msmtprc
defaults
auth           on
tls            on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile        /var/log/msmtp.log

account        default
host           %SMTP_HOST%
port           %SMTP_PORT%
from           %SMTP_FROM%
user           %SMTP_USER%
password       %SMTP_PASS%
EOF

RUN chmod 644 /etc/msmtprc \
    && touch /var/log/msmtp.log \
    && chown www-data:www-data /var/log/msmtp.log

# CORRECCIÓN: Copiar script de entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# CORRECCIÓN: Agregar healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl -f http://localhost/login/index.php || exit 1

EXPOSE 80

# CORRECCIÓN: Usar entrypoint para instalación automática
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
