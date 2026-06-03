# Banderas en el menú de selección de idioma

Esta guía explica cómo funcionan las banderas que aparecen a la izquierda de cada
opción del menú de idioma (y de la opción activa) en el tema **theme_almondb**, y
cómo **añadir banderas para nuevos idiomas**.

---

## 1. Cómo funciona

El menú de idioma de Moodle (heredado de `theme_boost`) solo entrega el texto de
cada idioma (p. ej. `English (en)`). El tema lo enriquece en tres capas:

| Capa | Archivo | Qué hace |
|------|---------|----------|
| **Datos (PHP)** | `lib/langflags.php` | Detecta el código de idioma de cada opción y le adjunta la URL de su bandera. |
| **Conexión** | `layout/frontpage.php` y `layout/drawers.php` | Pasan el menú por `theme_almondb_add_lang_flags()` antes de enviarlo a la plantilla. |
| **Vista (Mustache)** | `templates/theme_boost/language_menu.mustache` | Pinta `<img class="langflag">` a la izquierda del texto. |
| **Estilo (SCSS)** | `scss/almondb/_langflags.scss` | Tamaño y alineado de la bandera. |
| **Imágenes** | `pix/flags/*.svg` | Las banderas en sí. |

### Detección del código de idioma

`theme_almondb_detect_lang_code()` obtiene el código así:

1. Del atributo `lang`/`hreflang` del enlace (presente en las opciones **no** activas).
2. Si no existe (caso de la opción **activa**), lo extrae del texto entre paréntesis:
   `Español - Internacional (es)` → `es`.

### Elección de la bandera

`theme_almondb_lang_flag_url()` decide qué archivo usar:

1. Si existe `pix/flags/<código>.svg` y el código está en la lista blanca → se usa esa.
2. Si no, prueba con el **idioma base** (`pt_br` → `pt`).
3. Si tampoco hay → usa el globo genérico `pix/flags/_default.svg` (nunca se rompe la imagen).

---

## 2. Banderas incluidas

Actualmente el tema trae:

| Código | Archivo | Bandera |
|--------|---------|---------|
| `en` | `pix/flags/en.svg` | Reino Unido |
| `es` | `pix/flags/es.svg` | España |
| `fr` | `pix/flags/fr.svg` | Francia |
| `de` | `pix/flags/de.svg` | Alemania |
| `it` | `pix/flags/it.svg` | Italia |
| `pt` | `pix/flags/pt.svg` | Portugal |
| _(fallback)_ | `pix/flags/_default.svg` | Globo genérico |

La lista blanca vive en `lib/langflags.php`:

```php
$available = ['en', 'es', 'fr', 'de', 'it', 'pt'];
```

---

## 3. Cómo añadir una bandera para un idioma nuevo

> Ejemplo: añadir **ruso** (`ru`).

### Paso 1 — Crear el SVG

Crea el archivo `pix/flags/ru.svg`. El nombre **debe ser el código de idioma de
Moodle** (no el del país). Para variantes como `pt_br`, basta con tener `pt.svg`
(usa el idioma base automáticamente); crea `pt_br.svg` solo si quieres una bandera
distinta para esa variante específica.

Recomendaciones para el SVG:
- Sin tamaño fijo problemático: usa `viewBox` (se escala solo). Ej.: `viewBox="0 0 3 2"`.
- Diseño simple y reconocible; las tricolores son triviales.

Ejemplo (`ru.svg`, bandera de Rusia, tres franjas horizontales):

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 9 6">
  <rect width="9" height="6" fill="#fff"/>
  <rect width="9" height="4" y="2" fill="#0039A6"/>
  <rect width="9" height="2" y="4" fill="#D52B1E"/>
</svg>
```

### Paso 2 — Registrar el código en la lista blanca

Edita `lib/langflags.php` y agrega el código:

```php
$available = ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru'];
```

> Si **no** lo agregas a la lista, el idioma usará el globo `_default` aunque el
> archivo `ru.svg` exista.

### Paso 3 — Purgar cachés

Las imágenes `pix` y las plantillas se cachean. Tras añadir archivos:

```bash
# Dentro del contenedor (dirroot = /var/www/html/public)
docker exec moodle_app php /var/www/html/admin/cli/purge_caches.php
```

O desde la interfaz: **Administración del sitio → Desarrollo → Purgar todas las cachés**.

---

## 4. Ajustar el tamaño de las banderas

Edita `scss/almondb/_langflags.scss`:

```scss
.langflag {
    width: 1.3rem;     // ancho de la bandera
    height: 0.9rem;    // alto de la bandera
    object-fit: cover;
    border-radius: 2px;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.15);
}
```

Tras editar SCSS, purga cachés (Moodle recompila el SCSS al hacerlo).

---

## 5. Solución de problemas

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| Sale el **globo** en vez de la bandera | El código no está en `$available` o el archivo no existe. | Verifica el nombre `pix/flags/<código>.svg` y añade el código a la lista blanca. |
| **Imagen rota** (ícono de imagen) | El archivo no existe pero el código está en la lista, o falta purgar cachés. | Crea el SVG y purga cachés. |
| La bandera **no cambia** tras editar | Caché de pix/plantillas/SCSS. | Purga todas las cachés. |
| No aparece **ninguna** bandera | Solo hay un idioma instalado (Moodle oculta el menú) o `langmenu` no se procesó. | Instala 2+ idiomas; confirma que el layout usa `theme_almondb_add_lang_flags()`. |

---

## 6. ¿Qué bandera corresponde a cada idioma?

El código de Moodle es de **idioma**, no de país, así que la asociación es una
decisión de diseño. Convenciones usadas aquí:

- `en` → Reino Unido (el inglés "base" de Moodle es británico; `en_us` sería EE. UU.).
- `es` → España (aunque el paquete sea "Español - Internacional").

Si prefieres otra bandera para un idioma (p. ej. `en` → EE. UU.), simplemente
reemplaza el contenido de `pix/flags/en.svg`.

---

## Archivos relacionados

- `lib/langflags.php` — lógica de detección y asignación de banderas.
- `templates/theme_boost/language_menu.mustache` — plantilla del menú (override de boost).
- `scss/almondb/_langflags.scss` — estilos (importado en `scss/almondb-main.scss`).
- `layout/frontpage.php`, `layout/drawers.php` — conexión del post-procesado.
- `pix/flags/` — banderas SVG.
