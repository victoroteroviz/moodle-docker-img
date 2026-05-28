# Usamos PHP 8.2 que es el entorno recomendado para Moodle 5.2
FROM php:8.2-apache

# Habilitar mod_rewrite de Apache para URLs limpias
RUN a2enmod rewrite

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
RUN curl -L https://github.com/moodle/moodle/archive/refs/heads/MOODLE_502_STABLE.tar.gz \
    | tar xz -C /var/www/html --strip-components=1

# Inyectar config.php dinámico para que Moodle lea desde las variables de entorno
RUN cat <<'EOF' > /var/www/html/config.php
<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

// Configuración de Base de Datos
$CFG->dbtype    = getenv('MOODLE_DB_TYPE') ?: 'mysqli'; // 'mysqli' o 'pgsql'
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
    && chown -R www-data:www-data /var/www/html /var/www/moodledata \
    && chmod -R 755 /var/www/html /var/www/moodledata \
    && chmod 644 /var/www/html/config.php

# Exponer el puerto 80
EXPOSE 80

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]