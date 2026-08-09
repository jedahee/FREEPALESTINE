<?php
/**
 * Build CSS: concatena los módulos de css/ en un único style.css.
 *
 * Fuente: css/style.src.css (contiene los @import + reglas globales).
 * Salida: style.css (un solo archivo, sin peticiones bloqueantes extra).
 *
 * Uso: php build-css.php
 * Nota: reescribe rutas relativas ../../assets/ -> assets/ porque tras
 * concatenar el CSS vive en la raíz, no en css/<sub>/.
 */

$src_file = __DIR__ . '/css/style.src.css';
$out_file = __DIR__ . '/style.css';

if (!is_file($src_file)) {
    fwrite(STDERR, "No existe $src_file\n");
    exit(1);
}

$css = file_get_contents($src_file);

$css = preg_replace_callback(
    '/@import\s+url\((["\']?)([^"\')]+)\1\);\s*/',
    function ($m) {
        $path = __DIR__ . '/' . $m[2];
        if (!is_file($path)) {
            return "/* @import no encontrado: {$m[2]} */\n";
        }
        $content = file_get_contents($path);
        // La CSS de módulos usaba ../../assets/ relativo a css/<sub>/
        $content = str_replace('../../assets/', 'assets/', $content);
        return "/* ==== {$m[2]} ==== */\n" . $content . "\n";
    },
    $css
);

if (file_put_contents($out_file, $css) === false) {
    fwrite(STDERR, "No se pudo escribir $out_file\n");
    exit(1);
}

$kb = number_format(strlen($css) / 1024, 1);
echo "OK: style.css generado ({$kb} KiB)\n";
