<?php

// Esta función permite que PHP lea archivos con datos sensibles como un .env
function load_env($path) {
    if (!file_exists($path)) {
        throw new Exception("El archivo .env no existe");
    }

    // Leer el archivo .env línea por línea
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar líneas que son comentarios (comienzan con '#')
        if (strpos(trim($line), '#') === 0) 
            continue; // Salta a la siguiente iteración del bucle
        

        // Dividir la línea en clave y valor
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Añadir la clave y el valor a las variables de entorno
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}
load_env(__DIR__ . '/config/.env');

?>