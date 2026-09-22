<?php
/**
 * Sitemap XML dinámico.
 *
 * Genera el sitemap sobre la marcha para que las URLs de las opiniones de
 * "La Voz Palestina" y las 5 versiones de idioma estén siempre al día.
 * Se sirve en /sitemap.xml desde index.php.
 */

require_once __DIR__ . '/backend/i18n.php';
i18n_init('es');
require_once __DIR__ . '/backend/opinions.php';

$base = Utils::get_base_url();
$today = date('Y-m-d');
$langs = i18n_langs();

/** Lista de [{hreflang, url}] con las 5 versiones locales de una ruta. */
function fp_sitemap_localized($langs, $clean_path) {
    $out = [];
    foreach ($langs as $code => $meta) {
        $prefix = $code === 'es' ? '' : '/' . $code;
        $out[] = [
            'hreflang' => isset($meta['hreflang']) ? $meta['hreflang'] : $code,
            'url' => $prefix . $clean_path,
        ];
    }
    $out[] = ['hreflang' => 'x-default', 'url' => $clean_path];
    return $out;
}

$urls = [];

// Portada (una entrada con las 5 alternativas de idioma + x-default)
$urls[] = [
    'loc' => $base . '/',
    'lastmod' => $today,
    'changefreq' => 'daily',
    'priority' => '1.0',
    'hreflang' => fp_sitemap_localized($langs, '/'),
];

// La Voz Palestina (listado)
$urls[] = [
    'loc' => $base . '/la-voz-palestina',
    'lastmod' => $today,
    'changefreq' => 'weekly',
    'priority' => '0.8',
    'hreflang' => fp_sitemap_localized($langs, '/la-voz-palestina'),
];

// Opiniones individuales (un slug vale para los 5 idiomas)
foreach (get_published_opinions() as $entry) {
    $slug = rawurlencode($entry['slug'] ?? '');
    if ($slug === '') {
        continue;
    }
    $path = '/la-voz-palestina/' . $slug;
    $urls[] = [
        'loc' => $base . $path,
        'lastmod' => $entry['date'] ?? $today,
        'changefreq' => 'monthly',
        'priority' => '0.6',
        'hreflang' => fp_sitemap_localized($langs, $path),
    ];
}

// Páginas legales
$statics = [
    ['/aviso-legal', 'yearly', '0.3'],
    ['/politica-de-privacidad', 'yearly', '0.3'],
    ['/terminos-y-condiciones', 'yearly', '0.3'],
];
foreach ($statics as $s) {
    $urls[] = [
        'loc' => $base . $s[0],
        'lastmod' => '2026-08-03',
        'changefreq' => $s[1],
        'priority' => $s[2],
        'hreflang' => fp_sitemap_localized($langs, $s[0]),
    ];
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
foreach ($urls as $u) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
    echo '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $u['priority'] . "</priority>\n";
    foreach ($u['hreflang'] as $alt) {
        echo '    <xhtml:link rel="alternate" hreflang="' . htmlspecialchars($alt['hreflang'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($base . $alt['url'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"/>' . "\n";
    }
    echo "  </url>\n";
}
echo "</urlset>\n";