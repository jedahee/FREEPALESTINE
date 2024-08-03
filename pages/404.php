<?php 
require_once 'backend/utils.php';

$base_url = Utils::get_base_url();

?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <!-- META KEYS -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta http-equiv="X-UA-Compatible" content="IE=7" />
    <!-- /META KEYS -->

    <!-- FAVICON -->
    <link rel="icon" href="favicon.png" type="image/png" />
    <!-- Favicon para dispositivos Apple -->
    <link rel="apple-touch-icon" href="favicon.png" />
    <!-- Especificar tamaños para múltiples versiones -->
    <link rel="icon" href="./assets/media/favicon-32x32.png" sizes="32x32" type="image/png" />
    <link rel="icon" href="./assets/media/favicon-16x16.png" sizes="16x16" type="image/png" />
    <!-- /FAVICON -->

    <!-- STYLES -->
    <link rel="stylesheet" href="style.css" />
    <!-- /STYLES -->

    <title>FREE PALESTINE</title>
  </head>
<body class="pnf_body">
    <img src="favicon.png" alt="Logo FREE PALESTINE">
    <h1>Página no encontrada</h1>
    <p>Lo sentimos, la página que buscas no existe.</p>
    <p><a class="btn" href="<?php echo $base_url; ?>">Volver al inicio</a></p>
</body>
</html>
