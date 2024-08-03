<?php
// Esta página de entrada funciona como un routing simple

// Ruta a tu archivo 404.php
define('ERROR_404_PAGE', __DIR__ . '/pages/404.php');

// Obtener la ruta solicitada
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$base = '/freepalestine';
$route = str_replace($base, '', $request_uri);

switch ($route) {
    case '/':
    case '':
        // página principal
        include('pages/home.php');
        break;
    case '/aviso-legal':
        // Aviso legal
        include('legal/aviso-legal.php');
        break;

    case '/politica-de-privacidad':
        // Política de privacidad
        include('legal/politica-de-privacidad.php');
        break;
    case '/terminos-y-condiciones':
        // Términos y condiciones
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