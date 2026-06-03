# Orden y comportamiento del navbar en la portada

Documenta dos ajustes hechos sobre la barra de navegación (navbar) de la página de
inicio:

1. **Mover el navbar arriba del todo** (antes aparecía debajo del carrusel).
2. **Corregir el comportamiento sticky** para que no tape el carrusel.

---

## 1. Contexto

La portada se ensambla en `templates/frontpage/frontpage.mustache`. El tema tiene
tres variantes de cabecera, controladas por el ajuste **`frontpagenavchoice`**
(1, 2 o 3) en _Front Page_:

| Valor | Cabecera (`partial`) | Navbar |
|-------|----------------------|--------|
| 1 (por defecto) | `header1.mustache` | El navbar que se usa junto al slider/carrusel. |
| 2 | `header2.mustache` | Variante de cabecera. |
| 3 | `header3.mustache` | Variante de cabecera. |

El problema original: con `frontpagenavchoice = 1`, el carrusel (`slider`) se
renderizaba **antes** que `header1`, por lo que el navbar quedaba **debajo** del
carrusel.

---

## 2. Cambio 1 — Navbar hasta arriba

En `templates/frontpage/frontpage.mustache` se movió el bloque del navbar
(`header1`) **dentro del área de header, antes del slider**.

**Antes:**

```mustache
<!-- Start Slider Area -->
    {{> theme_almondb/frontpage/slider}}
    {{#frontpagenavchoice1}}
         {{> theme_almondb/frontpage/header1}}   {{! navbar debajo del carrusel }}
    {{/frontpagenavchoice1}}
<!-- End Slider Area -->
```

**Después:**

```mustache
<!-- Start Header Area -->
    {{#frontpagenavchoice1}}
         {{> theme_almondb/frontpage/header1}}   {{! navbar arriba del todo }}
    {{/frontpagenavchoice1}}
    {{#frontpagenavchoice2}}
         {{> theme_almondb/frontpage/header2}}
    {{/frontpagenavchoice2}}
    {{#frontpagenavchoice3}}
         {{> theme_almondb/frontpage/header3}}
    {{/frontpagenavchoice3}}
<!-- End Header Area -->
<!-- Start Slider Area -->
    {{> theme_almondb/frontpage/slider}}
<!-- End Slider Area -->
```

Resultado del orden de renderizado: **navbar → carrusel → secciones de bloques**.

---

## 3. Cambio 2 — Sticky que no tapa el carrusel

El navbar (`header1.mustache`) trae un pequeño JS que, al hacer scroll, le añade la
clase **`.stickyhy`** al contenedor `#navbarhy`. Como ahora el navbar está arriba
del todo, su `offsetTop` es ~0 y se volvía `position: fixed` casi de inmediato,
**flotando encima del carrusel** y tapándolo.

La corrección está en `scss/almondb/_navbar.scss`:

**Antes:**

```scss
.stickyhy {
    position: fixed;   /* se sale del flujo → tapa el carrusel */
    top: 0;
    width: 100%;
    padding-top: 0;
}
```

**Después:**

```scss
.stickyhy {
    position: sticky;  /* reserva su espacio → no tapa el carrusel */
    top: 0;
    width: 100%;
    padding-top: 0;
    z-index: 999;
}
```

### Por qué funciona

- `position: fixed` saca el elemento del flujo del documento → nada reserva su
  altura → el carrusel queda por debajo y el navbar lo cubre.
- `position: sticky` mantiene el navbar en su lugar (reservando su altura), así el
  carrusel empieza **justo debajo**; al hacer scroll el navbar se "pega" arriba de
  forma natural. El `z-index: 999` garantiza que quede por encima del contenido que
  pasa por debajo mientras está pegado.

---

## 4. Cómo cambiar el orden de los bloques de la portada

El orden de las secciones de contenido (no el navbar) se define en
`templates/frontpage/frontpage_1.mustache`, que incluye cada bloque en secuencia:

```mustache
{{> theme_almondb/frontpage/block01 }}
{{> theme_almondb/frontpage/block02 }}
{{> theme_almondb/frontpage/block09 }}
...
```

Para reordenar, cambia el orden de esas líneas.

---

## 5. Notas

- Tras editar plantillas o SCSS, **purga cachés** (Moodle recompila el SCSS):

  ```bash
  docker exec moodle_app php /var/www/html/admin/cli/purge_caches.php
  ```

- Si cambias `frontpagenavchoice` a 2 o 3, el navbar lo aportan `header2`/`header3`;
  revisa esas plantillas si necesitas el mismo ajuste de posición.

---

## Archivos relacionados

- `templates/frontpage/frontpage.mustache` — orden navbar/slider/secciones.
- `templates/frontpage/frontpage_1.mustache` — orden de los bloques de contenido.
- `templates/frontpage/header1.mustache` — navbar de la portada (incluye el JS sticky).
- `scss/almondb/_navbar.scss` — estilos del navbar y de `.stickyhy`.
- `settings/frontpage_settings.php` — ajuste `frontpagenavchoice`.
