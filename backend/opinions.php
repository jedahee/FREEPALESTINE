<?php

/**
 * Opiniones del público publicadas en la web.
 *
 * Las opiniones llegan por el formulario (o por correo) y el equipo las publica
 * a mano editando `backend/data/opinions.json`. Cada entrada admite:
 *
 *   slug         -> URL amable (reservado para futuro enrutado por entrada)
 *   title        -> título de la entrada
 *   author       -> nombre mostrado (p. ej. "Anónimo")
 *   author_url   -> enlace del colaborador (opcional; como "La voz afiliada"
 *                   de CNT Sevilla, enlazamos a quien colabora con nosotros)
 *   image        -> imagen ilustrativa: URL externa (https://...) o archivo
 *                   subido desde el formulario (ruta /uploads/...)
 *   document     -> documento adjunto de la opinión, ruta /uploads/... (opcional)
 *   date         -> fecha de publicación (YYYY-MM-DD)
 *   categories   -> categorías (sección a la que pertenece la entrada)
 *   tags         -> etiquetas libres (van al JSON-LD como keywords)
 *   excerpt      -> resumen corto para tarjetas y meta description
 *   body         -> párrafos del texto
 */
function get_published_opinions() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $file = __DIR__ . '/data/opinions.json';
    if (!is_file($file)) {
        $cache = [];
        return $cache;
    }

    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) {
        $cache = [];
        return $cache;
    }

    usort($data, function ($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });

    $cache = $data;
    return $cache;
}

/** Tiempo de lectura estimado (minutos, ~200 palabras/min). */
function opinion_reading_minutes(array $entry) {
    $words = 0;
    foreach (($entry['body'] ?? []) as $paragraph) {
        $parts = preg_split('/\s+/u', trim((string) $paragraph), -1, PREG_SPLIT_NO_EMPTY);
        $words += is_array($parts) ? count($parts) : 0;
    }
    return max(1, (int) ceil($words / 200));
}

/** Fecha de entrada formateada en el idioma actual. */
function format_opinion_date($date) {
    $ts = strtotime((string) $date);
    if ($ts === false) {
        return '';
    }
    try {
        return (new IntlDateFormatter(current_locale(), IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format($ts);
    } catch (Exception $e) {
        return date('d/m/Y', $ts);
    }
}

/** URL localizada de una entrada de La Voz Palestina. */
function opinion_url($slug) {
    return lang_url('/la-voz-palestina/' . rawurlencode((string) $slug));
}

/** Busca una entrada por slug; devuelve null si no existe. */
function get_opinion_by_slug($slug) {
    foreach (get_published_opinions() as $entry) {
        if (($entry['slug'] ?? '') === (string) $slug) {
            return $entry;
        }
    }
    return null;
}

/** Traduce una categoría (almacenada en español) al idioma actual. */
function t_category($cat) {
    $map = t('opinions.categories');
    return (is_array($map) && isset($map[$cat]) && $map[$cat] !== '') ? $map[$cat] : $cat;
}
