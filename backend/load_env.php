<?php
function load_env($path) {
    if (!file_exists($path)) {
        throw new Exception("El archivo .env no existe");
    }

    // Keys ya cargadas por load_env en este proceso. Permite recargar el .env
    // (servidores como `php -S`) sin congelar el valor del primer request.
    static $loadedKeys = [];

    // Leer el archivo .env línea por línea
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar líneas que son comentarios (comienzan con '#')
        if (strpos(trim($line), '#') === 0) {
            continue; // Salta a la siguiente iteración del bucle
        }

        // Dividir la línea en clave y valor
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        // Respetar variables ya definidas en el entorno REAL (Apache SetEnv,
        // export en shell...) salvo que load_env ya las haya cargado antes.
        if (!in_array($key, $loadedKeys, true)
            && (array_key_exists($key, $_SERVER) || array_key_exists($key, $_ENV))) {
            continue;
        }

        $loadedKeys[] = $key;
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
load_env(__DIR__ . '/config/.env');

?>