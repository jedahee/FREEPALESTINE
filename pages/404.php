<?php
require_once __DIR__ . '/../backend/i18n.php';
if (!isset($GLOBALS['__i18n'])) i18n_init();
$base_url = Utils::get_base_url();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>" dir="<?php echo current_dir(); ?>">

<head>
  <!-- META KEYS -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <meta name="description" content="<?php echo Utils::e(t('error404.meta_description')); ?>" />
  <meta name="robots" content="noindex, follow" />
  <meta property="og:site_name" content="Free Palestine">
  <meta property="og:title" content="<?php echo Utils::e(t('error404.meta_og_title')); ?>">
  <meta property="og:description" content="<?php echo Utils::e(t('error404.meta_og_description')); ?>">
  <meta property="og:url" content="<?php echo lang_url('/'); ?>">
  <meta property="og:image" content="<?php echo $base_url; ?>/og-image.jpg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Free Palestine — Firma por la libertad y justicia para Palestina">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="<?php echo current_locale(); ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@freepalestine">
  <meta name="twitter:title" content="<?php echo Utils::e(t('error404.meta_og_title')); ?>">
  <meta name="twitter:description" content="<?php echo Utils::e(t('error404.meta_og_description')); ?>">
  <meta name="twitter:image" content="<?php echo $base_url; ?>/og-image.jpg">
  <link rel="canonical" href="<?php echo lang_url('/'); ?>">
  <meta name="theme-color" content="#d80032">
  <!-- /META KEYS -->

  <!-- FAVICON -->
  <link rel="icon" href="<?php echo $base_url; ?>/favicon.png" type="image/png" />
  <link rel="apple-touch-icon" href="<?php echo $base_url; ?>/favicon.png" />
  <link rel="icon" href="<?php echo $base_url; ?>/assets/media/favicon-32x32.png" sizes="32x32" type="image/png" />
  <link rel="icon" href="<?php echo $base_url; ?>/assets/media/favicon-16x16.png" sizes="16x16" type="image/png" />
  <!-- /FAVICON -->

  <!-- STYLES -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>/style.css?v=<?= @filemtime('style.css') ?>" />
  <!-- /STYLES -->

  <title>FREE PALESTINE — <?php echo Utils::e(t('error404.title')); ?></title>
</head>

<body class="pnf_body">
  <img src="<?php echo $base_url; ?>/favicon.png" alt="Logo FREE PALESTINE">
  <h1><?php echo Utils::e(t('error404.title')); ?></h1>
  <p><?php echo Utils::e(t('error404.desc')); ?></p>
  <p><a class="btn" href="<?php echo lang_url('/'); ?>"><?php echo Utils::e(t('error404.btn')); ?></a></p>
</body>

</html>
