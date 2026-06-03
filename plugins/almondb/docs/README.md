# Documentación de personalizaciones — theme_almondb

Esta carpeta documenta las personalizaciones añadidas al tema **almondb** sobre la
versión original de ThemesAlmond.

## Índice

| Documento | Descripción |
|-----------|-------------|
| [video-intro.md](video-intro.md) | Video emergente en la portada para visitantes sin sesión (archivos y YouTube/Vimeo). |
| [navbar-orden.md](navbar-orden.md) | Navbar arriba del todo y corrección del comportamiento sticky sobre el carrusel. |
| [banderas-menu-idioma.md](banderas-menu-idioma.md) | Banderas a la izquierda de cada opción del menú de selección de idioma. |

## Notas generales

- Tras cambios en plantillas (`.mustache`), SCSS o imágenes `pix`, **purga las cachés**:

  ```bash
  docker exec moodle_app php /var/www/html/admin/cli/purge_caches.php
  ```

- En el contenedor, `dirroot` de Moodle es `/var/www/html/public` y la CLI está en
  `/var/www/html/admin/cli/`. El tema vive en `/var/www/html/public/theme/almondb`.

- Si añades nuevos ajustes o cambias `version.php`, ejecuta también el upgrade:

  ```bash
  docker exec moodle_app php /var/www/html/admin/cli/upgrade.php --non-interactive
  ```
