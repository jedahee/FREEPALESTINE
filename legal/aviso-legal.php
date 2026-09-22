<?php
require_once __DIR__ . '/../backend/i18n.php';
if (!isset($GLOBALS['__i18n'])) i18n_init();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>" dir="<?php echo current_dir(); ?>">
  <head>
    <base href="<?php echo Utils::get_base_url() . '/'; ?>">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="description" content="<?php echo Utils::e(t('legal.meta_aviso.description')); ?>" />
    <meta name="robots" content="index, follow" />

    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="<?php echo Utils::e(t('legal.meta_aviso.og_title')); ?>">
    <meta property="og:description" content="<?php echo Utils::e(t('legal.meta_aviso.og_description')); ?>">
    <meta property="og:url" content="<?php echo lang_url('/aviso-legal'); ?>">
    <meta property="og:image" content="<?php echo Utils::get_base_url(); ?>/og-image.jpg">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?php echo current_locale(); ?>">
    <link rel="canonical" href="<?php echo lang_url('/aviso-legal'); ?>">
    <?php foreach (hreflang_links() as $link): ?>
      <link rel="alternate" hreflang="<?php echo $link['hreflang']; ?>" href="<?php echo $link['url']; ?>">
    <?php endforeach; ?>

    <link rel="icon" href="favicon.png" type="image/png" />
    <link rel="apple-touch-icon" href="favicon.png" />
    <link rel="icon" href="./assets/media/favicon-32x32.png" sizes="32x32" type="image/png" />
    <link rel="icon" href="./assets/media/favicon-16x16.png" sizes="16x16" type="image/png" />

    <link rel="stylesheet" href="style.css?v=<?= @filemtime('style.css') ?>" />

    <title><?php echo Utils::e(t('legal.meta_aviso.title')); ?></title>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "<?php echo Utils::e(t('legal.meta_aviso.og_title')); ?>",
      "description": "<?php echo Utils::e(t('legal.meta_aviso.ld_desc')); ?>",
      "url": "<?php echo lang_url('/aviso-legal'); ?>",
      "inLanguage": "<?php echo current_lang(); ?>",
      "isPartOf": {
        "@type": "WebSite",
        "name": "Free Palestine",
        "url": "<?php echo Utils::get_base_url(); ?>/"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Free Palestine",
        "url": "<?php echo Utils::get_base_url(); ?>/"
      }
    }
    </script>
  </head>
<body class="legal">
    <a href="<?php echo lang_url('/'); ?>" class="btn back-home"><?php echo t('legal.back_home'); ?></a>
    <h1><?php echo t('legal.aviso_title'); ?></h1>
    <h3><?php echo t('legal.aviso_responsable'); ?></h3>
    <ul>
        <li><strong><?php echo t('legal.aviso_responsable_name'); ?></strong></li>
        <li><strong><?php echo t('legal.aviso_responsable_dir'); ?></strong></li>
        <li><strong><?php echo t('legal.aviso_responsable_email'); ?></strong></li>
    </ul>

    <h3><?php echo t('legal.aviso_objeto'); ?></h3>
    <p><?php echo t('legal.aviso_objeto_desc'); ?></p>

    <h3><?php echo t('legal.aviso_condiciones'); ?></h3>
    <p><?php echo t('legal.aviso_condiciones_desc'); ?></p>
    <p><a href="<?php echo lang_url('/'); ?>"><?php echo t('legal.back_home'); ?></a></p>
</body>
</html>
