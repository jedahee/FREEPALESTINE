# Proyecto de Firmas para Palestina

<img src="https://www.freepalestine.es/favicon.png" alt="Bandera Palestina" width="300"/>

> **Estado: ✅ Terminado** (2026-08-03) — sin bugs conocidos. En producción en https://freepalestine.es

## Descripción

Este proyecto es una plataforma web creada para recolectar firmas en apoyo a la causa palestina. La web permite a los usuarios firmar una petición proporcionando su nombre completo y correo electrónico, y asegura que cada firma sea única y segura.

La página incluye un **objetivo común de firmas**: alcanzar las **500.000 firmas**, umbral legal de una Iniciativa Legislativa Popular (ILP) en España (Ley Orgánica 3/1984). Al llegar, se ceden todas las firmas a asociaciones pro-palestinas para registrar la ILP en el Congreso: el único mecanismo ciudadano que el Estado español está obligado a tramitar.

## Características

- **Formulario de Firma**: Los usuarios pueden ingresar su nombre completo y correo electrónico para firmar la petición.
- **Validación de Firmas**: Se asegura que cada firma sea única y válida (verificación por correo con enlace de confirmación y cancelación).
- **Almacenamiento Seguro**: Los datos de los usuarios se cifran con **AES-256-CBC** y se almacenan en un archivo JSON.
- **Notificaciones por Correo**: Envía notificaciones por correo a los usuarios que firman y al administrador del sitio vía **SMTP** (Gmail, 500 correos/día) con respaldo automático a **EmailJS**.
- **Meta única (ILP)**: Barra de progreso global hacia las 500.000 firmas del objetivo común, con nota explicativa del mecanismo de ILP.
- **Voz pública**: formulario de opiniones con adjuntos (PDF/Word e imagen, máx. 5 MB), web personal opcional y publicación editorial en `/la-voz-palestina` con JSON-LD.
- **Opinión del público**: Formulario que sustituye al de contacto (`backend/send_email.php`); las mejores entradas se publican en la página `/la-voz-palestina` con autor (o anónimo), tiempo de lectura, categorías y etiquetas.
- **Eventos y manifestaciones**: Agenda colaborativa con [palestinalibre.es](https://palestinalibre.es) vía su API REST de The Events Calendar (`backend/events.php`, caché 6h).
- **Organizaciones colaboradoras**: Slider accesible con las organizaciones que apoyan la campaña (CNT Sevilla, Palestina libre…).
- **Recursos de sensibilización**: Sección con documentales, libros, podcasts, guías de boicot y medios de análisis **en español**.
- **Historias de resistencia**: Grid estático de tarjetas "El Grito de Palestina" enlazadas a reportajes y artículos reales.
- **Cifras en vivo**: Contador de víctimas en Gaza (fallecidos y niños) obtenido de `data.techforpalestine.org` con caché server-side (TTL 1h).
- **Video de fondo**: Hero con video de la causa palestina (autoplay, silenciado, en bucle) y overlay de scrim.
- **Sin dependencias externas**: No se carga ningún script o CSS de CDN (Swiper y AOS eliminados); fuentes auto-hospedadas.
- **SEO y datos estructurados**: Meta tags Open Graph/Twitter, JSON-LD (WebSite, Organization, WebPage, ItemList, Dataset de víctimas) con cifras dinámicas. La página `/la-voz-palestina` usa categorías y etiquetas como `articleSection` y `keywords` en cada `BlogPosting`.
- **Interfaz Intuitiva**: Diseño simple y fácil de usar para garantizar una experiencia de usuario amigable.
- **Soporte Multiplataforma**: Accesible desde dispositivos móviles y de escritorio.

## Tecnologías Utilizadas

- **Frontend**: HTML, CSS3, JavaScript (vanilla)
- **Backend**: PHP
- **Base de Datos**: JSON (con cifrado AES-256-CBC de los datos personales)
- **Correo**: SMTP (cliente PHP puro en `send_email.php`) como transporte principal, con respaldo a **EmailJS**
- **Datos**: API pública de [data.techforpalestine.org](https://data.techforpalestine.org/) con caché
- **Servidor**: Apache (mod_rewrite) / PHP built-in server para desarrollo
- **Sin dependencias de frontend**: cero librerías externas, solo JS/CSS vanilla y fuentes auto-hospedadas

## Estructura del Proyecto

```
FreePalestine/
├── index.php              # Router principal (rewrites a las páginas)
├── .htaccess              # Redirección HTTPS y reglas de Apache
├── .router.php            # Router para el servidor de desarrollo (php -S)
├── pages/
│   ├── home.php           # Página principal
│   ├── opinions.php       # Página /la-voz-palestina (opinión del público)
│   └── 404.php            # Página de error
├── legal/                 # Aviso legal, privacidad y términos
├── backend/
│   ├── save_signature.php # Guardado y validación de firmas
│   ├── send_email.php     # Envío de correos (EmailJS) + adjuntos del formulario (uploads/)
│   ├── download_stickers.php # Genera ZIP del pack de stickers (fuera de la UI, conservado)
│   ├── goals.php          # Objetivo común de la campaña (ILP 500k)
│   ├── events.php         # Agenda de palestinalibre.es (API + caché)
│   ├── opinions.php       # Lectura de opiniones publicadas
│   ├── utils.php          # Cifrado/descifrado y utilidades
│   ├── load_env.php       # Carga del .env
│   ├── data/              # signatures.json, codes.json, casualties_cache.json, opinions.json, palestinalibre_events_cache.json
│   └── config/.env        # Variables de entorno (NO subir a git)
├── assets/
│   ├── video/             # Video del hero
│   ├── images/            # Imágenes de la web
│   ├── media/             # Favicons
│   ├── stickers/          # Pack de stickers (PNG y SVG fuente, sin uso en la UI)
│   └── svg/               # Iconos y bandera
├── uploads/                # Adjuntos del formulario de opinión (ignorado por git)
├── config/config.json     # Configuración de endpoints
├── css/                   # Estilos modulares (variables + por sección)
├── main.js                # Lógica del frontend (sign, share, data, popup)
├── robots.txt
└── sitemap.xml
```

## Cómo Empezar

1. **Clona el Repositorio**:
    ```sh
    git clone https://github.com/jedahee/FreePalestine.git
    cd FreePalestine
    ```

2. **Configura el Proyecto**:
    - Configura los detalles del servidor y los correos electrónicos en los archivos de configuración.
    - Los archivos de configuración se encuentran en: `config/config.json` y `backend/config/.env`.
    - Variables principales del `.env`: `ENCRYPTION_KEY`, `CIPHER_METHOD`, `FILENAME_JSON`, `EMAILJS_*`, `FP_EMAIL`, `ALLOWED_HOSTS`, `CORS_ALLOWED_ORIGINS`.

3. **Ejecuta en local** (opcional):
    ```sh
    php -S 127.0.0.1:8000 .router.php
    ```

4. **Despliega**:
    - Sube los archivos a tu servidor Apache (la redirección HTTPS y el rewrite se configuran en `.htaccess`).
    - Accede a `index.php` desde tu navegador.

## Gestión local / producción

El proyecto se despliega por git y está preparado para que local y producción compartan el mismo código sin pisarse los datos.

- **`.env` por entorno (nunca se sube)**: copia `backend/config/.env.example` como `backend/config/.env` en cada entorno y rellena los valores reales.
    - **Local**: `ALLOWED_HOSTS=localhost,127.0.0.1`, `CORS_ALLOWED_ORIGINS=http://localhost:8000`.
    - **Producción**: `ALLOWED_HOSTS=freepalestine.es`, `CORS_ALLOWED_ORIGINS=https://freepalestine.es`. Si el servidor está detrás de un proxy de confianza (Cloudflare, Nginx…), descomenta `TRUST_PROXY_IP_HEADER`.
- **Datos fuera de git**: `backend/data/*.json` (firmas y códigos cifrados) y `backend/logs/` están en `.gitignore`. En producción, `git pull` **nunca toca tus firmas**: los archivos de datos son propios de cada servidor.
- **`ENCRYPTION_KEY`**: debe ser **la misma clave** en todos los entornos que compartan datos, y debe coincidir con la que cifró los datos existentes (cambiarla rompe el descifrado). Genera una nueva solo si vas a re-cifrar todos los datos: `php -r "echo bin2hex(random_bytes(32));"`.
- **Backup de datos**: copia `backend/data/*.json` con regularidad. Es tu única fuente de verdad de las firmas.
- **Permisos**: el usuario del servidor web debe poder escribir en `backend/data/` y `backend/logs/` (p. ej. `chown -R www-data:www-data backend/data backend/logs`).

> La configuración (`.env`) y los datos (`backend/data/`) viven solo en cada servidor; nunca se sobrescriben en un `git pull`.

## Contribución

Si deseas contribuir al proyecto, por favor sigue estos pasos:

1. **Fork el Repositorio**
2. **Crea una Rama**:
    ```sh
    git checkout -b feature-nueva-caracteristica
    ```
3. **Realiza tus Cambios y Haz Commit**:
    ```sh
    git commit -m "Agregada nueva característica"
    ```
4. **Sube la Rama**:
    ```sh
    git push origin feature-nueva-caracteristica
    ```
5. **Abre un Pull Request**

## Notas para Desarrolladores

- **Regenerar stickers PNG**: exporta desde los SVG de `assets/stickers/svg/` con **librsvg** (`rsvg-convert` o Python `gi Rsvg`) para conservar la transparencia. ImageMagick (sin `rsvg-convert`) aplanaba el fondo a blanco.
- **Entorno local**: `php -S 127.0.0.1:8000 .router.php` (el `.htaccess` no aplica con el built-in server).

## Categorías, etiquetas y datos estructurados

Las opiniones del público usan **categorías** (sección temática: `Opinión`, `Análisis`…) y **etiquetas** (temas concretos: `Gaza`, `BDS`…), siguiendo el modelo de "La voz afiliada" de CNT Sevilla. Cada entrada de `backend/data/opinions.json` define `categories` y `tags`.

Para SEO, la página `/la-voz-palestina` emite JSON-LD (`@graph`) donde:

- La colección es un `CollectionPage` con `hasPart` hacia cada entrada.
- Cada entrada es un `BlogPosting` con:
    - `articleSection` ← `categories`
    - `keywords` ← `tags` unidos por coma
    - `author` ← `Person` (con `url` si el autor tiene web; anónimo sin url)
    - `datePublished`, `headline`, `description` (excerpt), `publisher` → `#organization`

Así Google puede agrupar las entradas por sección y mostrar las etiquetas como temas tratados.

## Optimización de CSS (build)

El CSS se sirve en **un solo archivo** (`style.css`, sin `@import` ni requests en cascada) generado a partir de los módulos. **Edita siempre los módulos** en `css/`, nunca `style.css` directamente.

- `css/style.src.css` = fuente: `@import`s de los módulos + reglas globales (variables, tipografía, overlay).
- `build-css.php` = concatenador: lee la fuente, reemplaza `../../assets/` por `assets/` (las rutas son relativas al nuevo archivo), e inyecta el contenido de cada `@import` en orden (RTL al final).

**Workflow**:

```sh
# 1. Edita los módulos en css/ (p. ej. css/sections/header.css)
# 2. Regenera style.css
php build-css.php
# 3. Verifica en local: php -S 127.0.0.1:8000 .router.php
```

`build-css.php` y `css/style.src.css` están en git para que el build sea reproducible en cualquier entorno (PHP 7+).

## Imágenes WebP (recomendaciones)

Todas las imágenes servidas son **WebP** (junto a la caché de 1 año del `.htaccess`, esto evita re-descargar y reduce el peso inicial):

- **Generar con ImageMagick**: `convert entrada.png -resize 800x -quality 88 -strip salida.webp`. En este proyecto se usa 756px de ancho máximo para las tarjetas de historias y 800px para el fondo.
- **Ahorro conseguido aquí**: del 28% al 88% por imagen respecto a PNG/JPG originales (la imagen principal pasó de 212KB a 24KB).
- **No usar `<picture>` con fallback**: los navegadores modernos soportan WebP; un `<img src="*.webp">` directo ahorra HTML y descarta la descarga doble.
- **Calidad 88 como equilibrio**: por debajo se ven artefactos en fotos con degradados; por encima apenas se gana calidad.
- **No subir originales**: borra los PNG/JPG fuente tras generar el WebP (este repo elimina `main_image.jpg` de 2.7MB, `image2.png` de 13MB y los `pattern.*`).

## Video del hero (optimización)

`assets/video/video-palestine-home-3.mp4` está re-codificado a **720p H.264 (~655 kbps)** con ffmpeg, pasando de **6.3MB a 1.4MB (-78%)** sin pérdida perceptible gracias al overlay oscuro:

```sh
ffmpeg -i original.mp4 -vf "scale=1280:-2" -c:v libx264 -preset slow -crf 32 -c:a aac -b:a 96k -movflags +faststart -an video-720p.mp4
```

Criterios: CRF 32 para fondo con scrim (hasta ~2.3MB con CRF 28 si se aprecia pérdida en pantallas grandes), `-movflags +faststart` para playback inmediato y `-an` porque el hero va silenciado.

## Seguridad

- Las firmas se cifran con AES-256-CBC antes de almacenarse.
- Protección CSRF con token de sesión.
- Limitación de peticiones (rate limiting) en firma y envío de correo.
- Verificación por correo antes de confirmar una firma.
- El `.env` y el directorio `backend/config/` están bloqueados por `.htaccess` y `robots.txt`.

## Licencia

Este proyecto está bajo la Licencia MIT. Para más información, consulta el archivo [LICENSE](LICENSE).

## Agradecimientos

Agradecemos a todos los que han apoyado y contribuido a este proyecto. ¡Cada firma cuenta! ✊
