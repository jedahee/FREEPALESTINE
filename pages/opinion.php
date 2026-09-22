<?php

require_once 'backend/load_env.php';
require_once 'backend/utils.php';
require_once 'backend/goals.php';
require_once 'backend/opinions.php';
require_once 'backend/i18n.php';

if (!isset($GLOBALS['__i18n'])) i18n_init();

// El enrutado de index.php define $opinion_slug para /la-voz-palestina/{slug}
$slug = $opinion_slug ?? '';
$entry = get_opinion_by_slug($slug);

if ($entry === null) {
    http_response_code(404);
    include __DIR__ . '/../pages/404.php';
    exit;
}

$all_opinions = get_published_opinions();
$base_url = Utils::get_base_url();
$entry_url = opinion_url($entry['slug']);
$list_url = lang_url('/la-voz-palestina');

// Relacionadas: otras entradas, excluyendo la actual
$related = [];
foreach ($all_opinions as $o) {
    if (($o['slug'] ?? '') === ($entry['slug'] ?? '')) {
        continue;
    }
    if (count($related) >= 3) break;
    $related[] = $o;
}

// JSON-LD para la entrada individual
$entry_image = (string) ($entry["image"] ?? "");
if ($entry_image !== "" && preg_match('#^(https?://|/uploads/)#i', $entry_image)) {
    $entry_image = Utils::e($entry_image);
} else {
    $entry_image = "";
}
$author = $entry["author"] ?? t('opinions.anonymous');

$json_ld = [
    "@context" => "https://schema.org",
    "@graph" => [
        [
            "@type" => "Article",
            "@id" => $entry_url . "#article",
            "headline" => $entry["title"],
            "description" => $entry["excerpt"] ?? "",
            "url" => $entry_url,
            "datePublished" => ($entry["date"] ?? "") . "T00:00:00+02:00",
            "inLanguage" => current_lang(),
            "articleSection" => array_values($entry["categories"] ?? []),
            "keywords" => implode(", ", array_values($entry["tags"] ?? [])),
            "author" => [
                "@type" => "Person",
                "name" => $author,
                "url" => $entry["author_url"] ?? ""
            ],
            "publisher" => ["@id" => $base_url . "/#organization"],
            "mainEntityOfPage" => $entry_url,
            "isPartOf" => ["@id" => $list_url]
        ],
        [
            "@type" => "BreadcrumbList",
            "@id" => $entry_url . "#breadcrumb",
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
                    "item" => $list_url
                ],
                [
                    "@type" => "ListItem",
                    "position" => 3,
                    "name" => $entry["title"],
                    "item" => $entry_url
                ]
            ]
        ]
    ]
];

if ($entry_image !== "") {
    $json_ld["@graph"][0]["image"] = $entry_image;
}
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
    <meta name="description" content="<?php echo Utils::e($entry['excerpt'] ?? ''); ?>" />
    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="<?php echo Utils::e($entry['title']); ?>">
    <meta property="og:description" content="<?php echo Utils::e($entry['excerpt'] ?? ''); ?>">
    <meta property="og:url" content="<?php echo $entry_url; ?>">
    <meta property="og:image" content="<?php echo Utils::e($entry_image !== '' ? $entry_image : $base_url . '/og-image.jpg'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="article">
    <meta property="og:locale" content="<?php echo current_locale(); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo Utils::e($entry['title']); ?>">
    <meta name="twitter:description" content="<?php echo Utils::e($entry['excerpt'] ?? ''); ?>">
    <meta name="twitter:image" content="<?php echo Utils::e($entry_image !== '' ? $entry_image : $base_url . '/og-image.jpg'); ?>">
    <link rel="canonical" href="<?php echo $entry_url; ?>">
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

    <script type="application/ld+json"><?php echo json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

    <title><?php echo Utils::e($entry['title'] . ' — ' . t('opinions.title')); ?></title>
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
          <li><a href="<?php echo $list_url; ?>"><?php echo Utils::e(t('opinions.title')); ?></a></li>
          <li aria-current="page"><?php echo Utils::e($entry['title']); ?></li>
        </ol>
      </nav>

      <article class="opinion-detail">
        <header class="opinion-detail__header">
          <div class="opinion-detail__meta">
            <?php foreach (($entry['categories'] ?? []) as $cat): ?>
              <span class="chip chip--category"><?php echo Utils::e(t_category($cat)); ?></span>
            <?php endforeach; ?>
            <time datetime="<?php echo Utils::e($entry['date']); ?>"><?php echo Utils::e(format_opinion_date($entry['date'] ?? '')); ?></time>
          </div>

          <h1><?php echo Utils::e($entry['title']); ?></h1>

          <?php if (!empty($entry['excerpt'])): ?>
            <p class="opinion-detail__standfirst"><?php echo Utils::e($entry['excerpt']); ?></p>
          <?php endif; ?>

          <div class="opinion-detail__byline">
            <span class="opinion-detail__byline-label"><?php echo t('opinions.written_by'); ?></span>
            <?php if (!empty($entry['author_url'])): ?>
              <a class="opinion-detail__byline-name" href="<?php echo htmlspecialchars($entry['author_url'], ENT_QUOTES); ?>" target="_blank" rel="noopener"><strong><?php echo Utils::e($entry['author'] ?? t('opinions.anonymous')); ?></strong></a>
            <?php else: ?>
              <span class="opinion-detail__byline-name"><strong><?php echo Utils::e($entry['author'] ?? t('opinions.anonymous')); ?></strong></span>
            <?php endif; ?>
            <span class="opinion-detail__byline-date"><?php echo Utils::e(format_opinion_date($entry['date'] ?? '')); ?></span>
            <span class="opinion-detail__byline-sep" aria-hidden="true">·</span>
            <span class="opinion-detail__byline-read"><?php echo t('opinions.reading_time', ['count' => opinion_reading_minutes($entry)]); ?></span>
          </div>
        </header>

        <?php if ($entry_image !== ''): ?>
          <figure class="opinion-detail__figure">
            <img
              class="opinion-detail__image"
              src="<?php echo Utils::e($entry_image); ?>"
              alt="<?php echo Utils::e(sprintf('%s — %s', $entry['author'] ?? t('opinions.anonymous'), $entry['title'] ?? '')); ?>"
              loading="eager"
              decoding="async"
            />
          </figure>
        <?php endif; ?>

        <div class="opinion-detail__body">
          <?php $first = true; ?>
          <?php foreach (($entry['body'] ?? []) as $paragraph): ?>
            <p class="<?php echo $first ? 'has-drop-cap' : ''; ?>"><?php echo Utils::e($paragraph); ?></p>
            <?php $first = false; ?>
          <?php endforeach; ?>
        </div>

        <?php if (!empty($entry['tags'])): ?>
          <footer class="opinion-detail__tags">
            <span class="opinion-detail__tags-label"><?php echo t('opinions.topics'); ?></span>
            <div class="opinion-detail__tags-list">
              <?php foreach (($entry['tags'] ?? []) as $tag): ?>
                <span class="chip chip--tag">#<?php echo Utils::e($tag); ?></span>
              <?php endforeach; ?>
            </div>
          </footer>
        <?php endif; ?>
      </article>

      <div class="opinion-detail__share">
        <p class="opinion-detail__share-title" id="opinion-share-title"><?php echo Utils::e(t('opinions.share_title')); ?></p>
        <div class="opinion-detail__share-buttons" role="group" aria-labelledby="opinion-share-title">
          <a
            class="opinion-share opinion-share--fb"
            href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($entry_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo Utils::e(t('opinions.share_facebook')); ?>"
            title="<?php echo Utils::e(t('opinions.share_facebook')); ?>"
          >
            <span class="opinion-share__icon" aria-hidden="true"></span>
            <span class="opinion-share__name">Facebook</span>
          </a>
          <a
            class="opinion-share opinion-share--x"
            href="https://x.com/intent/post?url=<?php echo rawurlencode($entry_url); ?>&amp;text=<?php echo rawurlencode($entry['title']); ?>"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo Utils::e(t('opinions.share_x')); ?>"
            title="<?php echo Utils::e(t('opinions.share_x')); ?>"
          >
            <span class="opinion-share__icon" aria-hidden="true"></span>
            <span class="opinion-share__name">X</span>
          </a>
          <a
            class="opinion-share opinion-share--lk"
            href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode($entry_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo Utils::e(t('opinions.share_linkedin')); ?>"
            title="<?php echo Utils::e(t('opinions.share_linkedin')); ?>"
          >
            <span class="opinion-share__icon" aria-hidden="true"></span>
            <span class="opinion-share__name">LinkedIn</span>
          </a>
          <a
            class="opinion-share opinion-share--wh"
            href="https://api.whatsapp.com/send?text=<?php echo rawurlencode($entry['title'] . ' — ' . $entry_url); ?>"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="<?php echo Utils::e(t('opinions.share_whatsapp')); ?>"
            title="<?php echo Utils::e(t('opinions.share_whatsapp')); ?>"
          >
            <span class="opinion-share__icon" aria-hidden="true"></span>
            <span class="opinion-share__name">WhatsApp</span>
          </a>
          <button
            class="opinion-share opinion-share--copy"
            type="button"
            data-url="<?php echo Utils::e($entry_url); ?>"
            data-copied="<?php echo Utils::e(t('opinions.copied_link')); ?>"
            aria-label="<?php echo Utils::e(t('opinions.copy_link')); ?>"
            title="<?php echo Utils::e(t('opinions.copy_link')); ?>"
            onclick="copyOpinionLink(this)"
          >
            <span class="opinion-share__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
              </svg>
            </span>
            <span class="opinion-share__name"><?php echo Utils::e(t('opinions.copy_link')); ?></span>
          </button>
        </div>
        <p class="opinion-detail__share-hint"><?php echo Utils::e(t('opinions.share_hint')); ?></p>
      </div>

      <div class="opinion-detail__actions">
        <a class="opinion-detail__back" href="<?php echo $list_url; ?>"><?php echo t('opinions.back_to_list'); ?></a>
      </div>

      <?php if (!empty($related)): ?>
        <section class="opinions__related" aria-labelledby="related-title">
          <h2 id="related-title"><?php echo t('opinions.related_title'); ?></h2>
          <div class="opinions__grid">
            <?php foreach ($related as $entry): ?>
              <?php include __DIR__ . '/partials/opinion-card.php'; ?>
            <?php endforeach; ?>
          </div>
        </section>
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