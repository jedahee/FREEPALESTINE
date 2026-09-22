<?php

require_once 'backend/load_env.php';
require_once 'backend/utils.php';
require_once 'backend/goals.php';
require_once 'backend/opinions.php';
require_once 'backend/i18n.php';

if (!isset($GLOBALS['__i18n'])) i18n_init();

$all_opinions = get_published_opinions();
$base_url = Utils::get_base_url();
$page_url = lang_url('/la-voz-palestina');
$total_opinions = count($all_opinions);

// Paginación
$per_page = 6;
$total_pages = max(1, (int) ceil($total_opinions / $per_page));
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $per_page;
$opinions = array_slice($all_opinions, $offset, $per_page);

$canonical = $page > 1 ? $page_url . '?page=' . $page : $page_url;
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>" dir="<?php echo current_dir(); ?>">
  <head>
    <!-- URL base: mantiene relativas correctas en /en, /fr, /pt, /ar -->
    <base href="<?php echo $base_url . '/'; ?>">

    <!-- META KEYS -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="description" content="<?php echo Utils::e(t('opinions.meta_description', ['count' => t_num($total_opinions)])); ?>" />
    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="<?php echo Utils::e(t('opinions.meta_og_title')); ?>">
    <meta property="og:description" content="<?php echo Utils::e(t('opinions.meta_description', ['count' => t_num($total_opinions)])); ?>">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    <meta property="og:image" content="<?php echo $base_url; ?>/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?php echo current_locale(); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo Utils::e(t('opinions.meta_og_title')); ?>">
    <meta name="twitter:description" content="<?php echo Utils::e(t('opinions.meta_description', ['count' => t_num($total_opinions)])); ?>">
    <meta name="twitter:image" content="<?php echo $base_url; ?>/og-image.jpg">
    <link rel="canonical" href="<?php echo $canonical; ?>">
    <?php if ($page > 1): ?><link rel="prev" href="<?php echo $page_url; ?>"><link rel="next" href="<?php echo $page_url; ?>" /><?php endif; ?>
    <?php foreach (hreflang_links() as $link): ?>
      <link rel="alternate" hreflang="<?php echo $link['hreflang']; ?>" href="<?php echo $link['url']; ?>">
    <?php endforeach; ?>
    <meta name="theme-color" content="#d80032">
    <!-- /META KEYS -->

    <!-- FAVICON -->
    <link rel="icon" href="favicon.png" type="image/png" />
    <link rel="apple-touch-icon" href="favicon.png" />
    <link rel="icon" href="./assets/media/favicon-32x32.png" sizes="32x32" type="image/png" />
    <link rel="icon" href="./assets/media/favicon-16x16.png" sizes="16x16" type="image/png" />
    <!-- /FAVICON -->

    <!-- STYLES -->
    <link rel="stylesheet" href="style.css?v=<?= @filemtime('style.css') ?>" />
    <!-- /STYLES -->

    <?php
    // JSON-LD: CollectionPage + BreadcrumbList + ItemList con las entradas.
    $postings = [];
    foreach ($all_opinions as $i => $entry) {
        $entry_image = (string) ($entry["image"] ?? "");
        if ($entry_image !== "" && preg_match('#^(https?://|/uploads/)#i', $entry_image)) {
            $entry_image = Utils::e($entry_image);
        } else {
            $entry_image = "";
        }
        $posting = [
            "@type" => "BlogPosting",
            "@id" => opinion_url($entry["slug"] ?? '') . "#entry",
            "headline" => $entry["title"],
            "description" => $entry["excerpt"] ?? "",
            "datePublished" => ($entry["date"] ?? "") . "T00:00:00+02:00",
            "inLanguage" => current_lang(),
            "url" => opinion_url($entry["slug"] ?? ''),
            "author" => [
                "@type" => "Person",
                "name" => $entry["author"] ?? t('opinions.anonymous'),
                "url" => $entry["author_url"] ?? ""
            ],
            "publisher" => ["@id" => $base_url . "/#organization"],
            "mainEntityOfPage" => opinion_url($entry["slug"] ?? ''),
            "articleSection" => array_values($entry["categories"] ?? []),
            "keywords" => implode(", ", array_values($entry["tags"] ?? [])),
        ];
        if ($entry_image !== "") {
            $posting["image"] = $entry_image;
        }
        $postings[] = $posting;
    }

    $json_ld = [
        "@context" => "https://schema.org",
        "@graph" => [
            [
                "@type" => "CollectionPage",
                "@id" => $page_url,
                "url" => $page_url,
                "name" => t('opinions.title'),
                "description" => t('opinions.intro', ['count' => t_num($total_opinions)]),
                "inLanguage" => current_lang(),
                "isPartOf" => ["@id" => $base_url . "/#website"],
                "breadcrumb" => ["@id" => $page_url . "#breadcrumb"]
            ],
            [
                "@type" => "BreadcrumbList",
                "@id" => $page_url . "#breadcrumb",
                "itemListElement" => [
                    [
                        "@type" => "ListItem",
                        "position" => 1,
                        "name" => t('meta.breadcrumb_home'),
                        "item" => $base_url . "/"
                    ],
                    [
                        "@type" => "ListItem",
                        "position" => 2,
                        "name" => t('opinions.title'),
                        "item" => $page_url
                    ]
                ]
            ]
        ]
    ];

    if (!empty($postings)) {
        $json_ld["@graph"][0]["hasPart"] = $postings;
    }
    ?>
    <script type="application/ld+json"><?php echo json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

    <title><?php echo Utils::e(t('opinions.meta_title')); ?></title>
  </head>
  <body class="opinions-page">
    <header class="opinions-top">
      <nav>
        <?php include __DIR__ . '/partials/hero-top-bar.php'; ?>
      </nav>
    </header>
    <main class="opinions">
      <nav class="opinions__breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="<?php echo lang_url('/'); ?>"><?php echo Utils::e(t('meta.breadcrumb_home')); ?></a></li>
          <li aria-current="page"><?php echo Utils::e(t('opinions.title')); ?></li>
        </ol>
      </nav>

      <header class="opinions__header">
        <span class="eyebrow"><?php echo t('opinions.eyebrow'); ?></span>
        <h1><?php echo t('opinions.title'); ?></h1>
        <p><?php echo t('opinions.intro', ['count' => t_num($total_opinions)]); ?></p>
        <a class="opinions__back" href="<?php echo lang_url('/'); ?>"><?php echo t('opinions.back_to_home'); ?></a>
      </header>

      <?php if (empty($opinions)): ?>
        <div class="opinions__empty">
          <p><?php echo t('opinions.empty'); ?></p>
        </div>
      <?php else: ?>
        <div class="opinions__grid">
          <?php foreach ($opinions as $entry): ?>
            <?php include __DIR__ . '/partials/opinion-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
          <nav class="opinions__pagination" aria-label="<?php echo Utils::e(t('opinions.title')); ?>">
            <span class="opinions__pagination-label"><?php echo t('opinions.pagination.page', ['n' => $page]) ; ?> / <?php echo $total_pages; ?></span>

            <div class="opinions__pagination-controls">
              <?php if ($page > 1): ?>
                <a class="opinions__pagination-prev" href="<?php echo ($page > 2 ? $page_url . '?page=' . ($page - 1) : $page_url); ?>" rel="prev" aria-label="<?php echo t('opinions.pagination.prev'); ?>">&larr;</a>
              <?php else: ?>
                <span class="opinions__pagination-prev is-disabled" aria-hidden="true">&larr;</span>
              <?php endif; ?>

              <ol class="opinions__pagination-pages">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                  <?php if ($i === $page): ?>
                    <li><span class="is-current" aria-current="page"><?php echo $i; ?></span></li>
                  <?php else: ?>
                    <li><a href="<?php echo ($i === 1 ? $page_url : $page_url . '?page=' . $i); ?>"><?php echo $i; ?></a></li>
                  <?php endif; ?>
                <?php endfor; ?>
              </ol>

              <?php if ($page < $total_pages): ?>
                <a class="opinions__pagination-next" href="<?php echo $page_url . '?page=' . ($page + 1); ?>" rel="next" aria-label="<?php echo t('opinions.pagination.next'); ?>">&rarr;</a>
              <?php else: ?>
                <span class="opinions__pagination-next is-disabled" aria-hidden="true">&rarr;</span>
              <?php endif; ?>
            </div>
          </nav>
        <?php endif; ?>
      <?php endif; ?>

      <section class="opinions__cta">
        <h2><?php echo t('opinions.cta_title'); ?></h2>
        <p><?php echo t('opinions.cta_text'); ?></p>
        <a class="btn btn-primary" href="<?php echo lang_url('/'); ?>#share_opinion"><?php echo t('opinions.cta_btn'); ?></a>
      </section>
    </main>

    <!-- FOOTER -->
    <footer>
      <div class="footer-section">
        <h3><?php echo t('footer.brand'); ?></h3>
        <p><?php echo t('footer.desc'); ?></p>
        <p>
          <img class="footer-flag" src="assets/svg/flag-palestine.svg" alt="<?php echo Utils::e(t('footer.flag_alt')); ?>">
        </p>
      </div>
      <div class="footer-section">
        <h3><?php echo t('footer.participate'); ?></h3>
        <a href="<?php echo lang_url('/la-voz-palestina'); ?>"><?php echo t('footer.participate_opinions'); ?></a>
        <a href="<?php echo lang_url('/'); ?>#eventos"><?php echo t('footer.participate_events'); ?></a>
        <a href="<?php echo lang_url('/'); ?>#colabora"><?php echo t('footer.participate_support'); ?></a>
      </div>
      <div class="footer-section">
        <h3><?php echo t('footer.legal'); ?></h3>
        <a href="<?php echo lang_url('/aviso-legal'); ?>"><?php echo t('footer.legal_aviso'); ?></a>
        <a href="<?php echo lang_url('/politica-de-privacidad'); ?>"><?php echo t('footer.legal_priv'); ?></a>
        <a href="<?php echo lang_url('/terminos-y-condiciones'); ?>"><?php echo t('footer.legal_terms'); ?></a>
      </div>
    </footer>
    <!-- /FOOTER -->
    <script src="main.js?v=<?= @filemtime('main.js') ?>" defer></script>
  </body>
</html>