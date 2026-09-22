<?php

/**
 * Eventos de palestinalibre.es (plugin The Events Calendar de WordPress).
 *
 * Colaboración con Palestina Libre: mostramos sus convocatorias (manifestaciones,
 * talleres, festivales…) en Andalucía con enlace directo al detalle en su web.
 *
 * La API pública es https://palestinalibre.es/wp-json/tribe/events/v1/events.
 * Caché server-side de 6 horas para no golpear su servidor en cada visita;
 * si la API no responde se sirve la última caché buena disponible.
 */
function get_palestina_libre_events($limit = 6) {
    $cache_file = __DIR__ . '/data/palestinalibre_events_cache.json';
    $cache_ttl = 21600; // 6 horas

    if (is_file($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true,
            'user_agent' => 'FreePalestine/1.0 (+https://freepalestine.es; difusión colaborativa de eventos)',
        ],
    ]);

    $url = 'https://palestinalibre.es/wp-json/tribe/events/v1/events?per_page=' . (int) $limit;
    $data = @file_get_contents($url, false, $ctx);

    if ($data !== false) {
        $json = json_decode($data, true);
        if (isset($json['events']) && is_array($json['events'])) {
            $events = [];
            foreach ($json['events'] as $ev) {
                $text = trim(wp_strip_all_tags_compat((string) ($ev['description'] ?? '')));
                if (mb_strlen($text) > 220) {
                    $text = mb_substr($text, 0, 220) . '…';
                }
                $events[] = [
                    'title' => (string) ($ev['title'] ?? ''),
                    'url' => (string) ($ev['url'] ?? ''),
                    'start_date' => (string) ($ev['start_date'] ?? ''),
                    'venue' => (string) ($ev['venue']['venue'] ?? ''),
                    'city' => (string) ($ev['venue']['city'] ?? ''),
                    'excerpt' => $text,
                ];
            }
            @file_put_contents($cache_file, json_encode($events, JSON_UNESCAPED_UNICODE));
            return $events;
        }
    }

    // Fallback: última caché buena aunque esté caducada
    if (is_file($cache_file)) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached)) {
            return $cached;
        }
    }

    return [];
}

/** strip_tags tolerante (equivalente ligero a wp_strip_all_tags). */
function wp_strip_all_tags_compat($string) {
    $string = preg_replace('@<(script|style)[^>]*>.*?</\\1>@si', '', $string);
    $string = strip_tags($string);
    return trim(preg_replace('/[\\r\\n\\t ]+/', ' ', $string));
}

/** Fecha del evento formateada en el idioma actual (fallback manual en español). */
function format_event_date($mysql_date) {
    $ts = strtotime($mysql_date);
    if ($ts === false) {
        return '';
    }
    try {
        return (new IntlDateFormatter(
            current_locale(),
            IntlDateFormatter::LONG,
            IntlDateFormatter::SHORT
        ))->format($ts);
    } catch (Exception $e) {
        return date('d/m/Y H:i', $ts);
    }
}
