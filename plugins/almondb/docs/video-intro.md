# Video de intro en la portada

Esta funcionalidad muestra un **video emergente (popup)** al abrir la página de
inicio del sitio, **únicamente a visitantes sin sesión activa** (no autenticados).
Soporta archivos de video subidos y enlaces externos (YouTube / Vimeo).

---

## 1. Cómo usarlo (administrador)

1. Ve a **Administración del sitio → Apariencia → Temas → almondb → pestaña _Intro Video_**.
2. Marca **Enable intro video**.
3. Configura el origen del video (ver más abajo) y guarda.
4. Abre la portada en una ventana de incógnito (sin iniciar sesión) para verlo.

### Opciones disponibles

| Ajuste | Clave | Descripción |
|--------|-------|-------------|
| Enable intro video | `videointroenabled` | Activa/desactiva el video (apagado por defecto). |
| Video file | `videointrofile` | Sube un archivo (mp4, webm, ogg…). **Tiene prioridad** sobre la URL. |
| Video URL | `videointrourl` | Enlace externo. YouTube/Vimeo se incrustan automáticamente; otro enlace debe apuntar a un archivo de video directo. |
| Poster image | `videointroposter` | Imagen opcional mostrada antes de reproducir. |
| Autoplay | `videointroautoplay` | Reproduce solo al abrir (arranca **silenciado** para que el navegador lo permita). |
| Loop | `videointroloop` | Repite el video continuamente. |
| Show once per session | `videointroonce` | Solo lo muestra la primera vez por sesión del navegador (usa `sessionStorage`). |

---

## 2. Orígenes de video soportados

El origen se decide en `lib/videointro.php`:

| Origen | Cómo se reproduce |
|--------|-------------------|
| Archivo subido (`videointrofile`) | `<video>` directo |
| Enlace **YouTube** (`watch?v=`, `youtu.be`, `shorts`) | `<iframe>` de embed |
| Enlace **Vimeo** | `<iframe>` de embed |
| Otra URL directa (`.mp4`, `.webm`, `.ogg`…) | `<video>` directo |

> **Prioridad:** si hay archivo subido **y** URL, se usa el archivo. La URL solo
> aplica cuando no hay archivo.

---

## 3. Cómo funciona (técnico)

| Capa | Archivo | Qué hace |
|------|---------|----------|
| **Ajustes** | `settings/videointro.php` | Define la pestaña y los campos de configuración. |
| **Registro** | `settings.php` | `require('settings/videointro.php')`. |
| **Datos** | `lib/videointro.php` | `theme_almondb_videointro()` arma el contexto; detecta YouTube/Vimeo y construye la URL de embed; calcula el tipo MIME. |
| **Carga lib** | `lib.php` | `require('lib/videointro.php')` y registra el _filearea_ `videointro` en `theme_almondb_pluginfile()` (para servir el archivo subido). |
| **Conexión** | `layout/frontpage.php` | Mezcla el contexto del video con `theme_almondb_videointro()`. |
| **Vista** | `templates/frontpage/videointro.mustache` | Modal con `<iframe>` (embed) o `<video>` (archivo) + JS de control. |
| **Inclusión** | `templates/frontpage/frontpage.mustache` | Incluye el modal solo con `{{^userlogin}}` (sin sesión) y `{{#videointroenabled}}`. |
| **Estilo** | `scss/almondb/_videointro.scss` | Overlay, modal, wrapper 16:9 del iframe y botón de cierre. |
| **Idioma** | `lang/en/theme_almondb.php` | Cadenas `videointro*`. |

### Visibilidad: solo sin sesión

La condición está en la plantilla de la portada:

```mustache
{{^userlogin}}
    {{#videointroenabled}}
        {{> theme_almondb/frontpage/videointro }}
    {{/videointroenabled}}
{{/userlogin}}
```

`userlogin` lo define `layout/frontpage.php` (`true` si hay sesión). A los usuarios
autenticados **nunca** se les muestra el video.

### Comportamiento del popup

- Se cierra con la **✕**, haciendo clic en el **fondo**, con **Esc**, o
  automáticamente al **terminar** (si no está en loop).
- Con _Show once per session_ activo, no se vuelve a mostrar en la misma sesión del
  navegador (`sessionStorage`).
- El autoplay arranca **silenciado** (requisito de los navegadores); el visitante
  puede activar el sonido desde los controles.

---

## 4. Recomendaciones para el archivo de video

> ⚠️ **Importante:** los navegadores no decodifican de forma fiable **H.264 en 4K**
> (Level 5.1+). El síntoma es que se queda mostrando solo el póster y no reproduce.

Para máxima compatibilidad, sube el video en:

- **Códec:** H.264 (MP4).
- **Resolución:** 1080p o menor.
- **Faststart** (moov al inicio) para que empiece a reproducir antes de descargar todo.

Comando de referencia con `ffmpeg` para convertir a 1080p web-seguro:

```bash
ffmpeg -i entrada.mp4 \
  -vf "scale='min(1920,iw)':-2" \
  -c:v libx264 -profile:v high -level 4.0 -preset slow -crf 23 \
  -c:a aac -b:a 128k -movflags +faststart \
  salida-1080p.mp4
```

Para enlaces de **YouTube/Vimeo** esto no aplica: el proveedor se encarga de la
codificación y la compatibilidad.

---

## 5. Solución de problemas

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| Se queda el póster, no reproduce (archivo) | Video en 4K / H.264 Level alto. | Re-codifica a 1080p H.264 (ver comando arriba). |
| "No se ha encontrado ningún vídeo con formato/MIME compatible" | Pusiste un enlace de YouTube en versiones que solo usaban `<video>`, o una URL que no es archivo ni proveedor soportado. | Usa YouTube/Vimeo (se incrustan) o un enlace **directo** a un archivo de video. |
| El archivo subido da 404 | El _filearea_ no se sirve. | Confirma que `theme_almondb_pluginfile()` permite `videointro` y purga cachés. |
| El video se muestra a usuarios con sesión | La guarda `{{^userlogin}}` falta o se modificó. | Revisa `frontpage.mustache`. |
| No aparece tras configurarlo | Caché de plantillas. | Purga todas las cachés. |

Purgar cachés:

```bash
docker exec moodle_app php /var/www/html/admin/cli/purge_caches.php
```

---

## Archivos relacionados

- `settings/videointro.php` — pestaña de configuración.
- `lib/videointro.php` — lógica (contexto, embed YouTube/Vimeo, MIME).
- `templates/frontpage/videointro.mustache` — modal + JS.
- `templates/frontpage/frontpage.mustache` — inclusión con guarda de sesión.
- `scss/almondb/_videointro.scss` — estilos (importado en `scss/almondb-main.scss`).
- `layout/frontpage.php` — conexión del contexto.
- `lib.php` — carga de la lib y _filearea_ en `pluginfile`.
- `lang/en/theme_almondb.php` — cadenas de idioma.
