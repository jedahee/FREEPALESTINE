<?php
require_once __DIR__ . '/utils.php';

/**
 * Internacionalización: detección de idioma por prefijo de URL (/en, /fr...),
 * traducción (t()), generación de URLs por idioma y soporte RTL.
 *
 * Idiomas soportados. La clave es el prefijo de URL.
 * 'locale' se usa para og:locale e Intl.
 * 'dir'    es 'rtl' solo para árabe.
 */
function i18n_langs() {
    return [
        'es' => ['name' => 'Español', 'locale' => 'es_ES', 'dir' => 'ltr', 'hreflang' => 'es'],
        'en' => ['name' => 'English', 'locale' => 'en_US', 'dir' => 'ltr', 'hreflang' => 'en'],
        'fr' => ['name' => 'Français', 'locale' => 'fr_FR', 'dir' => 'ltr', 'hreflang' => 'fr'],
        'pt' => ['name' => 'Português', 'locale' => 'pt_PT', 'dir' => 'ltr', 'hreflang' => 'pt'],
        'ar' => ['name' => 'العربية', 'locale' => 'ar_SA', 'dir' => 'rtl', 'hreflang' => 'ar'],
    ];
}

/**
 * Inicializa el idioma actual a partir de la URL y carga sus traducciones.
 * Guarda todo en $GLOBALS['__i18n'].
 */
function i18n_init($forced_lang = null) {
    $langs = i18n_langs();

    $route = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $segments = explode('/', trim($route, '/'));
    $first = strtolower($segments[0] ?? '');

    $lang = 'es'; // idioma por defecto: / y /es
    if ($forced_lang !== null && isset($langs[$forced_lang])) {
        $lang = $forced_lang;
    } elseif (isset($langs[$first])) {
        $lang = $first;
        array_shift($segments);
    }

    // Ruta "limpia" sin prefijo de idioma (para reconstruir URLs en otros idiomas)
    $clean = '/' . implode('/', array_map('rawurldecode', $segments));
    if ($clean === '/' && $segments === []) {
        $clean = '/';
    }

    $strings = require __DIR__ . '/../lang/' . $lang . '.php';
    $meta = $langs[$lang];

    $GLOBALS['__i18n'] = [
        'lang' => $lang,
        'meta' => $meta,
        'dir' => $meta['dir'],
        'strings' => is_array($strings) ? $strings : [],
        'clean_path' => $clean,
        'explicit' => $forced_lang !== null || isset($langs[$first]),
    ];
}

/** Devuelve el código del idioma actual (p. ej. 'es'). */
function current_lang() {
    return $GLOBALS['__i18n']['lang'] ?? 'es';
}

/** Devuelve la dirección del idioma actual: 'ltr' o 'rtl'. */
function current_dir() {
    return $GLOBALS['__i18n']['dir'] ?? 'ltr';
}

/** Devuelve la localidad para Intl/og:locale (p. ej. 'es_ES'). */
function current_locale() {
    return $GLOBALS['__i18n']['meta']['locale'] ?? 'es_ES';
}

/**
 * Mejor idioma soportado según el header Accept-Language del navegador.
 * Devuelve un código de i18n_langs() o 'es' si no coincide ninguno.
 */
function i18n_browser_lang() {
    $langs = i18n_langs();
    $header = trim($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    if ($header === '') {
        return 'es';
    }
    $prefs = [];
    foreach (explode(',', $header) as $part) {
        $tokens = explode(';', trim($part));
        $tag = strtolower(trim($tokens[0]));
        $q = 1.0;
        if (isset($tokens[1]) && preg_match('/\bq=([0-9.]+)/', $tokens[1], $m)) {
            $q = (float) $m[1];
        }
        if ($tag === '') {
            continue;
        }
        $prefs[$tag] = isset($prefs[$tag]) ? max($prefs[$tag], $q) : $q;
    }
    arsort($prefs);
    foreach (array_keys($prefs) as $tag) {
        $primary = strtolower(explode('-', $tag)[0]); // en-US -> en
        if (isset($langs[$primary])) {
            return $primary;
        }
    }
    return 'es';
}

/**
 * Redirige (302) a la versión en el idioma del navegador cuando el usuario
 * entra sin idioma explícito en la URL (p. ej. visita la raíz '/').
 * - Si la URL ya trae prefijo de idioma (/en, /fr...), se respeta y se
 *   recuerda la elección en una cookie.
 * - Si el usuario eligió idioma antes (cookie fp_lang), se respeta esa elección.
 * - Solo actúa sobre GET/HEAD (navegación), nunca sobre POST/API.
 */
function i18n_maybe_redirect() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        return;
    }
    $langs = i18n_langs();

    // Idioma explícito en la URL: respetar y recordar la elección.
    if ($GLOBALS['__i18n']['explicit'] ?? false) {
        i18n_remember(current_lang());
        return;
    }

    $pref = isset($_COOKIE['fp_lang']) && isset($langs[$_COOKIE['fp_lang']])
        ? $_COOKIE['fp_lang']
        : i18n_browser_lang();

    if ($pref === 'es' || $pref === current_lang()) {
        return;
    }

    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . lang_url($pref) . ($qs !== '' ? '?' . $qs : ''), true, 302);
    exit;
}

/** Guarda la elección de idioma del usuario en una cookie (2 años). */
function i18n_remember($lang) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('fp_lang', $lang, [
        'expires' => time() + 63072000,
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => $secure,
        'httponly' => false,
    ]);
}

/** Ruta actual sin prefijo de idioma (p. ej. '/aviso-legal'). */
function clean_route() {
    return $GLOBALS['__i18n']['clean_path'] ?? '/';
}

/**
 * Traduce una clave con notación de puntos ('share.sign_btn').
 * Los parámetros se sustituyen con {clave}.
 */
function t($key, $params = []) {
    $strings = $GLOBALS['__i18n']['strings'] ?? [];
    $value = $strings;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $key; // clave inexistente: mostramos la clave como fallback visible
        }
        $value = $value[$part];
    }
    if (is_array($value) || $value === null) {
        return $value;
    }
    foreach ($params as $k => $v) {
        $value = str_replace('{' . $k . '}', (string) $v, $value);
    }
    return $value;
}

/**
 * URL de la ruta actual en el idioma dado (o el actual si no se pasa).
 * Ej.: lang_url() en /en/aviso-legal        -> base/en/aviso-legal
 *      lang_url('fr') en /aviso-legal       -> base/fr/aviso-legal
 *      lang_url('/terminos-y-condiciones')  -> ruta dada en el idioma actual
 * Para 'es' no se añade prefijo (es el idioma por defecto en la raíz).
 */
function lang_url($arg = null) {
    $base = Utils::get_base_url();
    $langs = i18n_langs();

    if ($arg === null) {
        $lang = current_lang();
        $route = clean_route();
    } elseif (isset($langs[$arg])) {
        // lang_url('fr') → ruta actual en 'fr'
        $lang = $arg;
        $route = clean_route();
    } else {
        // lang_url('/aviso-legal') → ruta dada en el idioma actual
        $lang = current_lang();
        $route = $arg;
    }

    $route = ($route === '' || $route === '/') ? '' : $route;
    $prefix = $lang === 'es' ? '' : '/' . $lang;
    if ($route === '') {
        return $base . $prefix . '/';
    }
    return $base . $prefix . $route;
}

/**
 * Links alternates hreflang para la ruta actual.
 * Devuelve array de ['hreflang' => 'es', 'url' => 'https://...'].
 */
function hreflang_links() {
    $links = [];
    foreach (i18n_langs() as $code => $meta) {
        $links[] = [
            'hreflang' => $meta['hreflang'],
            'url' => lang_url($code),
        ];
    }
    $links[] = [
        'hreflang' => 'x-default',
        'url' => lang_url('es'),
    ];
    return $links;
}

/** Carga las traducciones usadas por JavaScript (window.I18N). */
function js_i18n() {
    return t('js');
}

/**
 * Formatea un número con los separadores del idioma actual.
 * Español: 1.234,56 · Inglés: 1,234.56 · Resto: 1.234,56
 */
function t_num($number, $decimals = 0) {
    $separators = [
        'es' => ['.', ','],
        'en' => [',', '.'],
        'fr' => ['.', ','],
        'pt' => ['.', ','],
        'ar' => ['.', ','],
    ];
    [$thousands, $decimal] = $separators[current_lang()] ?? ['.', ','];
    return number_format((float) $number, $decimals, $decimal, $thousands);
}
