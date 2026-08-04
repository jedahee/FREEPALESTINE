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
    <meta name="description" content="Aviso legal de Free Palestine: responsable de la web, objeto, condiciones de uso y datos de contacto de la campaña de firmas en apoyo al pueblo palestino." />
    <meta name="robots" content="index, follow" />

    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="Aviso Legal — Free Palestine">
    <meta property="og:description" content="Aviso legal de la campaña Free Palestine: responsable, objeto y condiciones de uso de la web.">
    <meta property="og:url" content="<?php echo $base_url; ?>/aviso-legal">
    <meta property="og:image" content="<?php echo $base_url; ?>/og-image.jpg">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_ES">
    <link rel="canonical" href="<?php echo $base_url; ?>/aviso-legal">

    <link rel="icon" href="<?php echo $base_url; ?>/favicon.png" type="image/png" />
    <link rel="apple-touch-icon" href="<?php echo $base_url; ?>/favicon.png" />
    <link rel="icon" href="<?php echo $base_url; ?>/assets/media/favicon-32x32.png" sizes="32x32" type="image/png" />
    <link rel="icon" href="<?php echo $base_url; ?>/assets/media/favicon-16x16.png" sizes="16x16" type="image/png" />

    <link rel="stylesheet" href="<?php echo $base_url; ?>/style.css" />

    <title>Aviso Legal — Free Palestine</title>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Aviso Legal — Free Palestine",
      "description": "Aviso legal de la campaña de firmas Free Palestine en apoyo al pueblo palestino.",
      "url": "<?php echo $base_url; ?>/aviso-legal",
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
    <h1>AVISO LEGAL</h1>
    <h3>Responsable de la web:</h3>
    <ul>
        <li><strong>Nombre: JESÚS D.H.</strong></li>
        <li><strong>Dirección: ESPAÑA, SEVILLA</strong></li>
        <li><strong>Email: frpalestinee@gmail.com</strong></li>
    </ul>

    <h3>Objeto de la web:</h3>
    <p>La presente web tiene como objetivo la recolección de firmas en apoyo a la causa Palestina.</p>

    <h3>Condiciones de uso:</h3>
    <p>Los usuarios deben utilizar la web de manera responsable y de acuerdo con las leyes vigentes. El propietario de la web no se responsabiliza por el uso indebido por parte de los usuarios.</p>
    <p><a href="<?php echo $base_url; ?>">Volver al inicio</a></p>
</body>
</html>
