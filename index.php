<?php
// Ruta a tu archivo 404.php
define('ERROR_404_PAGE', __DIR__ . '/pages/404.php');

require_once __DIR__ . '/backend/i18n.php';

// Detectar idioma por prefijo de URL (/es, /en, /fr, /pt, /ar)
i18n_init();

// Redirige automáticamente al idioma del navegador si entras sin idioma explícito
i18n_maybe_redirect();

// Obtener la ruta solicitada sin el prefijo de idioma
$route = clean_route();

// Aquí decides qué hacer con base en la URL solicitada
switch ($route) {
    case '/':
    case '':
        // Aquí incluirías tu página principal
        include('pages/home.php');
        break;

    case '/aviso-legal':
        // Aquí incluirías tu página principal
        include('legal/aviso-legal.php');
        break;

    case '/politica-de-privacidad':
        // Aquí incluirías tu página principal
        include('legal/politica-de-privacidad.php');
        break;
    case '/terminos-y-condiciones':
        // Aquí incluirías tu página principal
        include('legal/terminos-y-condiciones.php');
        break;


    default:
        // Para cualquier otra URL, muestra el error 404
        http_response_code(404);
        include(ERROR_404_PAGE);
        exit();
        break;
}
?>
