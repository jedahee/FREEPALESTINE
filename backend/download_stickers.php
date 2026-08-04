<?php

$stickers_dir = __DIR__ . '/../assets/stickers';
$files = glob($stickers_dir . '/*.png');

if (empty($files)) {
    http_response_code(404);
    echo 'No hay stickers disponibles.';
    exit;
}

$zip = new ZipArchive();
$zip_name = 'freepalestine-stickers-' . date('Ymd') . '.zip';
$zip_tmp = tempnam(sys_get_temp_dir(), 'stickers');

if ($zip->open($zip_tmp, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo 'No se pudo generar el paquete.';
    exit;
}

foreach ($files as $file) {
    $zip->addFile($file, basename($file));
}

$readme = <<<TXT
PACK DE STICKERS — FREE PALESTINE
=================================

Gracias por descargar nuestro pack de stickers para visibilizar
la causa palestina. Comparte, difunde y sensibiliza.

¿Cómo instalarlos en WhatsApp?
1. Instala la app gratuita "Sticker Maker" (Android/iOS) o "Sticker.ly".
2. Abre la app y elige la opción de importar/crear un pack.
3. Añade las imágenes PNG de esta carpeta (una por una).
4. Pulsa "Añadir a WhatsApp" y ¡listo!

También puedes enviar cualquier PNG directamente como sticker
desde la galería en algunos dispositivos.

Diseños originales de Free Palestine.
TXT;

$zip->addFromString('LEEME.txt', $readme);
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_name . '"');
header('Content-Length: ' . filesize($zip_tmp));
header('Cache-Control: no-store');
readfile($zip_tmp);
unlink($zip_tmp);
exit;
