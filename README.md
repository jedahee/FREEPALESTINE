# Proyecto de Firmas para Palestina

<img src="https://www.freepalestine.es/favicon.png" alt="Bandera Palestina" width="300"/>

> **Estado: ✅ Terminado** (2026-08-03) — sin bugs conocidos. En producción en https://freepalestine.es

## Descripción

Este proyecto es una plataforma web creada para recolectar firmas en apoyo a la causa palestina. La web permite a los usuarios firmar una petición proporcionando su nombre completo y correo electrónico, y asegura que cada firma sea única y segura.

La página incluye una campaña con **metas progresivas de firmas** (500, 1.000, 2.500, 5.000, 10.000, 20.000 y 50.000) que van desbloqueando entregas para visibilizar la causa, como un pack de stickers descargable.

## Características

- **Formulario de Firma**: Los usuarios pueden ingresar su nombre completo y correo electrónico para firmar la petición.
- **Validación de Firmas**: Se asegura que cada firma sea única y válida (verificación por correo con enlace de confirmación y cancelación).
- **Almacenamiento Seguro**: Los datos de los usuarios se cifran con **AES-256-CBC** y se almacenan en un archivo JSON.
- **Notificaciones por Correo**: Envía notificaciones por correo a los usuarios que firman y al administrador del sitio vía **SMTP** (Gmail, 500 correos/día) con respaldo automático a **EmailJS**.
- **Metas de campaña**: Timeline de metas con barra de progreso relativa a la siguiente meta y entregas desbloqueables (stickers, recursos…).
- **Pack de stickers**: Al superar las 500 firmas se desbloquea un popup con los 8 stickers originales y se descarga un ZIP (`backend/download_stickers.php`) para instalar en WhatsApp.
- **Recursos de sensibilización**: Sección con documentales, libros, podcasts, guías de boicot y medios de análisis **en español** (entrega de la meta de 1000 firmas).
- **Historias de resistencia**: Grid estático de tarjetas "El Grito de Palestina" enlazadas a reportajes y artículos reales.
- **Cifras en vivo**: Contador de víctimas en Gaza (fallecidos y niños) obtenido de `data.techforpalestine.org` con caché server-side (TTL 1h).
- **Video de fondo**: Hero con video de la causa palestina (autoplay, silenciado, en bucle) y overlay de scrim.
- **Sin dependencias externas**: No se carga ningún script o CSS de CDN (Swiper y AOS eliminados); fuentes auto-hospedadas.
- **SEO y datos estructurados**: Meta tags Open Graph/Twitter, JSON-LD (WebSite, Organization, WebPage, ItemList de historias y metas, Dataset de víctimas) con cifras dinámicas.
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
│   └── 404.php            # Página de error
├── legal/                 # Aviso legal, privacidad y términos
├── backend/
│   ├── save_signature.php # Guardado y validación de firmas
│   ├── send_email.php     # Envío de correos (EmailJS)
│   ├── download_stickers.php # Genera ZIP del pack de stickers
│   ├── goals.php          # Definición de metas de la campaña
│   ├── utils.php          # Cifrado/descifrado y utilidades
│   ├── load_env.php       # Carga del .env
│   ├── data/              # signatures.json, codes.json, casualties_cache.json
│   └── config/.env        # Variables de entorno (NO subir a git)
├── assets/
│   ├── video/             # Video del hero
│   ├── images/            # Imágenes de la web
│   ├── media/             # Favicons
│   ├── stickers/          # Pack de stickers (PNG y SVG fuente)
│   └── svg/               # Iconos y bandera
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
