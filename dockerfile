# Moodle 5.2+ requiere PHP 8.3 como mínimo
FROM php:8.3-apache

# Composer se usa para generar vendor/autoload y metadata esperada por Moodle 5.2.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Moodle 5.2 requiere exponer solo el directorio public en el servidor web.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Habilitar mod_rewrite de Apache para URLs limpias
RUN a2enmod rewrite

# Ajustar DocumentRoot y directivas de Apache al directorio publico de Moodle.
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilitar .htaccess en public para que Moodle configure su router correctamente.
RUN cat <<'EOF' > /etc/apache2/conf-available/moodle-public.conf
<Directory /var/www/html/public>
    AllowOverride All
    Require all granted
</Directory>
EOF

RUN a2enconf moodle-public

# Ajustes recomendados por Moodle para seguridad y formularios grandes.
RUN cat <<'EOF' > /usr/local/etc/php/conf.d/moodle-recommended.ini
zend.exception_ignore_args=1
max_input_vars=5000
EOF

# Instalar dependencias del sistema y bibliotecas necesarias
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
    && docker-php-ext-configure gd --with-jpeg \
    # Compilar e instalar extensiones críticas para Moodle
    && docker-php-ext-install gd intl xml soap zip mysqli pdo pdo_mysql pdo_pgsql opcache sodium mbstring exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Descargar Moodle 5.2 (Rama MOODLE_502_STABLE) directamente al root del servidor web
# --no-same-owner evita que tar intente restaurar propietarios del archivo comprimido;
# se establece www-data desde el inicio para no necesitar chown -R posterior.
RUN curl -L https://github.com/moodle/moodle/archive/refs/heads/MOODLE_502_STABLE.tar.gz \
    | tar xz -C /var/www/html --strip-components=1 --no-same-owner \
    && chown www-data:www-data /var/www/html

RUN composer install --no-dev --classmap-authoritative --no-interaction --working-dir=/var/www/html

# Inyectar config.php dinámico para que Moodle lea desde las variables de entorno
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

// Configuración de Rutas y Permisos
$CFG->wwwroot   = getenv('MOODLE_URL') ?: 'http://localhost';
$CFG->dataroot  = getenv('MOODLE_DATA_DIR') ?: '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 02777;

// Forzar SSL si se usa un proxy inverso (como Traefik, Nginx o Caddy)
if (getenv('MOODLE_REVERSE_PROXY') === 'true') {
    $CFG->sslproxy = true;
}

require_once(__DIR__ . '/lib/setup.php');
EOF

# Crear el directorio de datos (moodledata) y ajustar permisos y dueños (vital en Linux)
RUN mkdir -p /var/www/moodledata \
    && chown www-data:www-data /var/www/moodledata \
    && chmod 750 /var/www/moodledata \
    && chmod 644 /var/www/html/config.php

# Exponer el puerto 80
EXPOSE 80

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]