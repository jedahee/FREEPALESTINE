<?php
require_once __DIR__ . '/../backend/utils.php';
$base_url = Utils::get_base_url();
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="description" content="Política de privacidad de Free Palestine: qué datos recopilamos (nombre y correo para las firmas), cómo los usamos, cómo los protegemos y los derechos de los usuarios." />
    <meta name="robots" content="index, follow" />

    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="Política de Privacidad — Free Palestine">
    <meta property="og:description" content="Cómo tratamos tus datos personales en la campaña de firmas Free Palestine.">
    <meta property="og:url" content="<?php echo $base_url; ?>/politica-de-privacidad">
    <meta property="og:image" content="<?php echo $base_url; ?>/og-image.jpg">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_ES">
    <link rel="canonical" href="<?php echo $base_url; ?>/politica-de-privacidad">

    <link rel="icon" href="<?php echo $base_url; ?>/favicon.png" type="image/png" />
    <link rel="apple-touch-icon" href="<?php echo $base_url; ?>/favicon.png" />
    <link rel="icon" href="<?php echo $base_url; ?>/assets/media/favicon-32x32.png" sizes="32x32" type="image/png" />
    <link rel="icon" href="<?php echo $base_url; ?>/assets/media/favicon-16x16.png" sizes="16x16" type="image/png" />

    <link rel="stylesheet" href="<?php echo $base_url; ?>/style.css" />

    <title>Política de Privacidad — Free Palestine</title>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Política de Privacidad — Free Palestine",
      "description": "Política de privacidad de la campaña de firmas Free Palestine.",
      "url": "<?php echo $base_url; ?>/politica-de-privacidad",
      "inLanguage": "es",
      "isPartOf": {
        "@type": "WebSite",
        "name": "Free Palestine",
        "url": "<?php echo $base_url; ?>/"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Free Palestine",
        "url": "<?php echo $base_url; ?>/"
      }
    }
    </script>
  </head>

  <body class="legal">
    <a href="<?php echo $base_url; ?>" class="btn back-home">&larr; Volver al inicio</a>
    <h1>Política de Privacidad</h1>

    <h2>Qué datos recopilamos</h2>
    <p>
        Recopilamos el nombre completo y el correo electrónico de los usuarios.
    </p>

    <h2>Cómo utilizamos los datos</h2>
    <p>
        Los datos son utilizados únicamente para la recolección de firmas y comunicación con los firmantes.
    </p>

    <h2>Cómo almacenamos y protegemos los datos</h2>
    <p>
        Los datos se almacenan de manera segura y se toman medidas para protegerlos contra el acceso no autorizado.
    </p>

    <h2>Derechos de los usuarios</h2>
    <p>
        Los usuarios pueden solicitar el acceso, rectificación o eliminación de sus datos personales en cualquier momento.
    </p>

    <h2>Contacto</h2>
    <p>
        Para ejercer sus derechos, los usuarios pueden contactarnos a través del email: <a href="mailto:frpalestinee@gmail.com">frpalestinee@gmail.com</a>.
    </p>
    <p><a href="<?php echo $base_url; ?>">Volver al inicio</a></p>
</body>
</html>