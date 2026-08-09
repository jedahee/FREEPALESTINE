<?php 

require_once 'backend/load_env.php';
require_once 'backend/utils.php';
require_once 'backend/goals.php';
require_once 'backend/i18n.php';

if (!isset($GLOBALS['__i18n'])) i18n_init();

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$total_signatures = count(Utils::readJsonFile('backend/' . getenv('FILENAME_JSON')));

function get_gaza_casualties() {
    $cache_file = 'backend/data/casualties_cache.json';
    $cache_ttl = 3600;
    if (is_file($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached) && isset($cached['killed'], $cached['children'])) {
            return $cached;
        }
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true,
            'user_agent' => 'FreePalestine/1.0'
        ]
    ]);
    $data = @file_get_contents('https://data.techforpalestine.org/api/v3/summary.json', false, $ctx);
    if ($data !== false) {
        $json = json_decode($data, true);
        if (isset($json['gaza']['killed']['total'], $json['gaza']['killed']['children'])) {
            $result = [
                'killed' => (int) $json['gaza']['killed']['total'],
                'children' => (int) $json['gaza']['killed']['children']
            ];
            @file_put_contents($cache_file, json_encode($result));
            return $result;
        }
    }
    if (is_file($cache_file)) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached) && isset($cached['killed'], $cached['children'])) {
            return $cached;
        }
    }
    return ['killed' => 0, 'children' => 0];
}

$casualties = get_gaza_casualties();

$dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

try {
    $fecha = (new IntlDateFormatter(current_locale(), IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(time());
} catch (Exception $e) {
    $fecha = $dias[date('w')] . ', ' . date('j') . ' de ' . $meses[date('n')-1] . ', ' . date('Y');
}
?>

<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>" dir="<?php echo current_dir(); ?>">
  <head>
    <!-- URL base: mantiene relativas (style.css, assets, backend, config...) correctas en /en, /fr, /pt, /ar -->
    <base href="<?php echo Utils::get_base_url() . '/'; ?>">

    <!-- META KEYS -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta name="description" content="<?php echo Utils::e(t('meta.description')); ?>" />
    <meta name="keywords" content="<?php echo Utils::e(t('meta.keywords')); ?>" />
    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="<?php echo Utils::e(t('meta.og_title')); ?>">
    <meta property="og:description" content="<?php echo Utils::e(t('meta.og_description')); ?>">
    <meta property="og:url" content="<?php echo lang_url(); ?>">
    <meta property="og:image" content="<?php echo Utils::get_base_url(); ?>/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo Utils::e(t('meta.og_image_alt')); ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="<?php echo current_locale(); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@freepalestine">
    <meta name="twitter:title" content="<?php echo Utils::e(t('meta.og_title')); ?>">
    <meta name="twitter:description" content="<?php echo Utils::e(t('meta.og_description')); ?>">
    <meta name="twitter:image" content="<?php echo Utils::get_base_url(); ?>/og-image.jpg">
    <link rel="canonical" href="<?php echo lang_url(); ?>">
    <?php foreach (hreflang_links() as $link): ?>
      <link rel="alternate" hreflang="<?php echo $link['hreflang']; ?>" href="<?php echo $link['url']; ?>">
    <?php endforeach; ?>
    <meta name="theme-color" content="#d80032">
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

    <!-- SCRIPT -->
    <script src="main.js" defer></script>
    <!-- /SCRIPT -->
    
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title><?php echo Utils::e(t('meta.title')); ?></title>

    <?php
    // Enlaces de historias por idioma (las 4 tarjetas de "El Grito de Palestina")
    $story_links_by_lang = [
        'es' => [
            "https://huelladelsur.ar/2025/05/16/palestina-el-grito-de-resistencia-que-interpela-a-toda-la-humanidad/",
            "https://www.aljazeera.com/where/palestine/",
            "https://www.bbc.co.uk/news/articles/cw0v8d805ypo",
            "https://www.resumenlatinoamericano.org/2024/03/13/palestina-desde-gaza-una-historia-de-amor-y-resistencia/",
        ],
        'en' => [
            "https://huelladelsur.ar/2025/05/16/palestina-el-grito-de-resistencia-que-interpela-a-toda-la-humanidad/",
            "https://www.aljazeera.com/where/palestine/",
            "https://www.bbc.co.uk/news/articles/cw0v8d805ypo",
            "https://www.resumenlatinoamericano.org/2024/03/13/palestina-desde-gaza-una-historia-de-amor-y-resistencia/",
        ],
        'fr' => [
            "https://www.middleeasteye.net/fr/actu-et-enquetes/guerre-israel-palestine-gaza-camps-refugies-nakba-intifada-histoire-occupation",
            "https://www.france-palestine.org/De-la-Nakba-au-genocide-une-vie-marquee-par-la-perte-et-la-resilience-pour-une",
            "https://www.middleeasteye.net/fr/actu-et-enquetes/guerre-gaza-israel-genocide-palestiniens-experts-crimes-etat",
            "https://www.amnesty.fr/actualites/rapport-genocide-palestiniens-gaza-commis-par-etat-israel/",
        ],
        'pt' => [
            "https://www.brasildefato.com.br/2024/10/07/horror-em-numeros-genocidio-de-israel-na-faixa-de-gaza-completa-um-ano/",
            "https://www.cartacapital.com.br/sociedade/manifestacao-em-sp-denuncia-contrato-com-empresa-israelense-envolvida-em-genocidio-palestino",
            "https://jornalggn.com.br/geopolitica/a-america-central-diante-do-genocidio-palestino-por-bruno-beaklini",
            "https://www.brasildefato.com.br/podcast/brasil-de-fato-entrevista/2024/09/25/brics-deve-liderar-reconstrucao-de-gaza-junto-com-palestinos-diz-presidente-da-federacao-palestina-do-brasil",
        ],
        'ar' => [
            "https://www.aljazeera.net/news/2025/7/17/%D9%83%D8%A7%D9%85%D9%8A%D8%B1%D8%A7-%D9%85%D9%82%D8%A7%D8%A8%D9%84-%D9%83%D9%8A%D8%B3-%D8%B7%D8%AD%D9%8A%D9%86-%D9%85%D8%A3%D8%B3%D8%A7%D8%A9-%D8%B5%D8%AD%D9%81%D9%8A-%D9%85%D9%86",
            "https://www.aljazeera.net/politics/2024/10/21/%D8%A7%D9%84%D9%85%D8%AC%D8%A7%D8%B9%D8%A9-%D8%AA%D8%B4%D8%AA%D8%AF-%D9%88%D8%A7%D9%84%D8%A7%D8%AD%D8%AA%D9%84%D8%A7%D9%84-%D9%8A%D8%AE%D9%84%D9%82-%D8%A8%D9%8A%D8%A6%D8%A9",
            "https://pchrgaza.org/ar/category/genocide-on-gaza-ar/testimonies-from-the-war-ar/",
            "https://studies.aljazeera.net/ar/article/6420",
        ],
    ];
    $story_links = $story_links_by_lang[current_lang()] ?? $story_links_by_lang['es'];

    $stories = [
        [
            "name" => t('basic_info.stories.1.title'),
            "description" => t('basic_info.stories.1.desc'),
            "url" => $story_links[0],
            "image" => "assets/images/image7.jpg"
        ],
        [
            "name" => t('basic_info.stories.2.title'),
            "description" => t('basic_info.stories.2.desc'),
            "url" => $story_links[1],
            "image" => "assets/images/image5.jpg"
        ],
        [
            "name" => t('basic_info.stories.3.title'),
            "description" => t('basic_info.stories.3.desc'),
            "url" => $story_links[2],
            "image" => "assets/images/image6.png"
        ],
        [
            "name" => t('basic_info.stories.4.title'),
            "description" => t('basic_info.stories.4.desc'),
            "url" => $story_links[3],
            "image" => "assets/images/image1.png"
        ]
    ];

    $base_url = Utils::get_base_url();

    $story_items = [];
    foreach ($stories as $i => $story) {
        $story_items[] = [
            "@type" => "ListItem",
            "position" => $i + 1,
            "name" => $story["name"],
            "url" => $story["url"],
            "image" => $base_url . "/" . $story["image"],
            "description" => $story["description"]
        ];
    }

    $json_ld = [
        "@context" => "https://schema.org",
        "@graph" => [
            [
                "@type" => "WebSite",
                "@id" => $base_url . "/#website",
                "url" => lang_url(),
                "name" => "Free Palestine",
                "description" => t('meta.site_desc'),
                "inLanguage" => current_lang(),
                "publisher" => ["@id" => $base_url . "/#organization"]
            ],
            [
                "@type" => "Organization",
                "@id" => $base_url . "/#organization",
                "name" => "Free Palestine",
                "url" => lang_url(),
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $base_url . "/favicon.png",
                    "width" => 512,
                    "height" => 512
                ],
                "email" => "frpalestinee@gmail.com",
                "sameAs" => [
                    "https://github.com/jedahee/FreePalestine"
                ]
            ],
            [
                "@type" => "WebPage",
                "@id" => lang_url() . "#webpage",
                "url" => lang_url(),
                "name" => t('meta.title'),
                "description" => t('meta.og_description'),
                "inLanguage" => current_lang(),
                "isPartOf" => ["@id" => $base_url . "/#website"],
                "about" => [
                    "@type" => "CreativeWork",
                    "name" => t('meta.about_name'),
                    "description" => t('meta.about_desc')
                ],
                "dateModified" => date("c"),
                "breadcrumb" => ["@id" => $base_url . "/#breadcrumb"],
                "primaryImageOfPage" => [
                    "@type" => "ImageObject",
                    "url" => $base_url . "/og-image.jpg",
                    "width" => 1200,
                    "height" => 630
                ],
                "mainEntity" => [
                    "@type" => "ItemList",
                    "name" => t('meta.about_name'),
                    "description" => t('meta.about_desc'),
                    "numberOfItems" => count($stories),
                    "itemListElement" => $story_items
                ]
            ],
            [
                "@type" => "BreadcrumbList",
                "@id" => $base_url . "/#breadcrumb",
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
                        "name" => t('meta.breadcrumb_sign'),
                        "item" => $base_url . "/#share_sign"
                    ]
                ]
            ]
        ]
    ];

    $json_ld_milestones = [
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "name" => t('meta.milestones_name'),
        "description" => t('meta.milestones_desc', ['count' => t_num($total_signatures)]),
        "numberOfItems" => count($goals),
        "itemListElement" => array_map(function ($index, $goal) use ($total_signatures) {
            $unlocked = $total_signatures >= $goal["signatures"];
            return [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => t('meta.goal_label', ['count' => t_num($goal["signatures"])]) . ($unlocked ? " · " . t('meta.goal_done') : " · " . t('meta.goal_locked')),
                "description" => $unlocked ? t('goals.' . $goal["signatures"]) : t('meta.goal_to_discover')
            ];
        }, array_keys($goals), $goals)
    ];
    ?>
    <script type="application/ld+json"><?php echo json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <script type="application/ld+json" id="ld-milestones"><?php echo json_encode($json_ld_milestones, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <script type="application/ld+json" id="ld-casualties">
    {
      "@context": "https://schema.org",
      "@type": "Dataset",
      "name": "<?php echo Utils::e(t('meta.casualties_name')); ?>",
      "description": "<?php echo Utils::e(t('meta.casualties_desc')); ?>",
      "url": "https://data.techforpalestine.org/",
      "inLanguage": "<?php echo current_lang(); ?>",
      "creator": {
        "@id": "<?php echo $base_url; ?>/#organization"
      },
      "publisher": {
        "@id": "<?php echo $base_url; ?>/#organization"
      },
      "variableMeasured": [
        {
          "@type": "PropertyValue",
          "name": "<?php echo Utils::e(t('meta.casualties_killed')); ?>",
          "value": <?php echo $casualties['killed']; ?>
        },
        {
          "@type": "PropertyValue",
          "name": "<?php echo Utils::e(t('meta.casualties_children')); ?>",
          "value": <?php echo $casualties['children']; ?>
        }
      ]
    }
    </script>
  </head>
  <body>
    <div class="loader-container hidden">
      <div class="loader"></div>
    </div>

    <div class="popup hidden">
      <section>
        <div class="icon error"></div>
        <div class="icon success"></div>

        <p class="msg"></p>
      </section>
      <div class="icon close"></div>
    </div>

    <?php 
    if (isset($_GET["sign"]) && $_GET["sign"] == "true") {
    ?>

    <div class="notification">
      <h2><?php echo t('notifications.sign_ok_title'); ?></h2>
      <p><?php echo t('notifications.sign_ok_desc', ['count' => t_num($total_signatures)]); ?></p>
      <p><?php echo t('notifications.sign_ok_social'); ?></p>
      <div class="share nt">
        <div class="notification__share">
          <div class="icon icon-fb" onclick="shareOnFacebook()"></div>
          <div class="icon icon-tw" onclick="shareOnTwitter()"></div>
          <div class="icon icon-lk" onclick="shareOnLinkedIn()"></div>
          <div class="icon icon-wh" onclick="shareOnWhatsApp()"></div>
        </div>
      </div>
      <a class="btn btn-primary"><?php echo t('notifications.close'); ?></a>
    </div>

    <?php
    } else if (isset($_GET["sign"]) && $_GET["sign"] == "false") {
    ?>
      <div class="notification cancel">
        <h2><?php echo t('notifications.sign_cancel_title'); ?></h2>
        <p><?php echo t('notifications.sign_cancel_desc'); ?></p>
        <a class="btn btn-primary"><?php echo t('notifications.close'); ?></a>
      </div>
    <?php
    } else if (isset($_GET["sign"]) && $_GET["sign"] == "error") {
    ?>
      <div class="notification error">
        <h2><?php echo t('notifications.sign_error_title'); ?></h2>
        <p><?php echo t('notifications.sign_error_desc'); ?></p>
        <a class="btn btn-primary"><?php echo t('notifications.close'); ?></a>
      </div>
    <?php
    }
    ?>
    
    <!-- HERO HEADER -->
    <header class="hero">
      <video class="hero-video" autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1">
        <source src="assets/video/video-palestine-home-3.mp4" type="video/mp4" />
      </video>
      <div class="hero-video-overlay" aria-hidden="true"></div>
      <div class="hero-top">
        <nav>
          <div class="hero-top-bar">
            <div class="date"><?php echo $fecha; ?></div>
            <div class="lang-switcher" data-lang-switcher>
              <div class="lang-switcher__wrap">
                <button
                  type="button"
                  class="lang-switcher__trigger"
                  aria-haspopup="listbox"
                  aria-expanded="false"
                  aria-label="<?php echo Utils::e(t('lang_switcher.label')); ?>"
                >
                  <img
                    class="lang-switcher__flag"
                    src="assets/svg/flags/<?php echo current_lang(); ?>.svg"
                    alt=""
                    width="24"
                    height="16"
                    aria-hidden="true"
                  />
                  <span class="lang-switcher__name"><?php echo i18n_langs()[current_lang()]['name']; ?></span>
                  <span class="lang-switcher__caret" aria-hidden="true"></span>
                </button>
                <ul class="lang-switcher__menu" role="listbox" aria-label="<?php echo Utils::e(t('lang_switcher.label')); ?>">
                <?php foreach (i18n_langs() as $code => $meta): ?>
                  <li role="option" aria-selected="<?php echo $code === current_lang() ? 'true' : 'false'; ?>">
                    <a
                      href="<?php echo lang_url($code); ?>"
                      class="lang-switcher__option<?php echo $code === current_lang() ? ' is-active' : ''; ?>"
                      hreflang="<?php echo $meta['hreflang']; ?>"
                      lang="<?php echo $meta['hreflang']; ?>"
                      title="<?php echo Utils::e(t('lang_switcher.title') . ': ' . $meta['name']); ?>"
                    >
                      <img
                        class="lang-switcher__flag"
                        src="assets/svg/flags/<?php echo $code; ?>.svg"
                        alt=""
                        width="24"
                        height="16"
                        aria-hidden="true"
                        loading="lazy"
                      />
                      <span class="lang-switcher__name"><?php echo $meta['name']; ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
              </div>
              <span class="lang-switcher__new"><?php echo t('lang_switcher.new'); ?></span>
            </div>
          </div>
        </nav>
        <h1><?php echo Utils::e(t('hero.title')); ?></h1>
        <p class="hero-subtitle"><?php echo Utils::e(t('hero.subtitle')); ?></p>
      </div>

      <?php
        $current_goal_index = null;
        foreach ($goals as $index => $goal) {
          if ($total_signatures < $goal["signatures"]) {
            $current_goal_index = $index;
            break;
          }
        }

        if ($current_goal_index !== null) {
          $next_goal = $goals[$current_goal_index];
          $prev_sig = $current_goal_index > 0 ? $goals[$current_goal_index - 1]["signatures"] : 0;
          $next_goal_range = $next_goal["signatures"] - $prev_sig;
          $next_goal_pct = min(100, round((($total_signatures - $prev_sig) / $next_goal_range) * 100, 2));
        } else {
          $next_goal_pct = 100;
        }
      ?>

      <div class="milestone-stats">
        <div class="stat-card">
          <span class="stat-number"><?php echo t_num($total_signatures); ?></span>
          <span class="stat-label"><?php echo t('stats.signatures'); ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?php echo count($goals); ?></span>
          <span class="stat-label"><?php echo t('stats.goals'); ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?php echo $next_goal_pct; ?>%</span>
          <span class="stat-label"><?php echo t('stats.next_goal'); ?></span>
        </div>
      </div>

      <div class="global-progress-track">
        <div class="global-progress-fill" style="width: <?php echo $next_goal_pct; ?>%;"></div>
      </div>

      <div class="timeline-wrap">
        <div class="timeline-track">
          <?php
            $milestone_count = count($goals);
            foreach ($goals as $index => $goal):
              $is_completed = $total_signatures >= $goal["signatures"];
              $is_current = $index === $current_goal_index;
              $is_locked = $current_goal_index !== null && $index > $current_goal_index;

              if ($is_current) {
                $prev = $index > 0 ? $goals[$index - 1]["signatures"] : 0;
                $range = $goal["signatures"] - $prev;
                $goal_pct = min(100, round((($total_signatures - $prev) / $range) * 100, 2));
              } elseif ($is_completed) {
                $goal_pct = 100;
              } else {
                $goal_pct = 0;
              }

              $dot_class = 'milestone-dot';
              if ($is_completed) $dot_class .= ' done';
              if ($is_current) $dot_class .= ' active';
              if ($is_locked) $dot_class .= ' locked';
          ?>
            <div class="milestone <?php if ($is_locked) echo 'locked'; ?>">
              <div class="<?php echo $dot_class; ?>">
                <?php if ($is_completed): ?>
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3"><polyline points="4,12 9,17 20,6"/></svg>
                <?php else: ?>
                  <span><?php echo $index + 1; ?></span>
                <?php endif; ?>
              </div>
              <div class="milestone-body">
                <span class="milestone-count"><?php echo t_num($goal["signatures"]); ?></span>
                <?php if (!$is_locked): ?>
                  <p><?php echo t('goals.' . $goal["signatures"]); ?></p>
                  <?php if ($is_current): ?>
                    <div class="milestone-progress">
                      <div class="milestone-progress-bar" style="width: <?php echo $goal_pct; ?>%;"></div>
                    </div>
                  <?php endif; ?>
                  <?php if ($is_completed && !empty($goal["deliverable"])): ?>
                    <button
                      type="button"
                      class="goal-deliverable"
                      data-deliverable="<?php echo htmlspecialchars($goal["deliverable"]); ?>"
                    >
                      <?php echo $goal["deliverable"] === "stickers" ? t('milestones.deliverable_stickers') : t('milestones.deliverable_resources'); ?>
                    </button>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
              <?php if ($index < $milestone_count - 1): ?>
                <div class="milestone-connector <?php if ($is_completed) echo 'done'; ?>"></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <a class="btn btn-hero" href="<?php echo lang_url() . '#share_sign'; ?>"><?php echo t('hero.sign_btn'); ?></a>
    </header>
    <!-- /HERO HEADER -->

    <!-- CONTENT -->
    <main>

      <!-- BASIC INFO -->
      <section class="basic-info">
        <header class="basic-info__header">
          <span class="eyebrow"><?php echo t('basic_info.eyebrow'); ?></span>
          <h2><?php echo t('basic_info.title'); ?></h2>
        </header>

        <div class="basic-info__body">
          <div class="basic-info__narrative">
            <blockquote class="basic-info__lead">
              <span class="lead-mark">“</span>
              <?php echo t('basic_info.lead1'); ?>
              <?php echo t('basic_info.lead2'); ?>
            </blockquote>
            <div class="basic-info__paragraphs">
              <p><?php echo t('basic_info.p1'); ?></p>
              <p><?php echo t('basic_info.p2'); ?></p>
              <p><?php echo t('basic_info.p3'); ?></p>
              <p><?php echo t('basic_info.p4'); ?></p>
            </div>
          </div>

          <div class="basic-info__moments">
            <div class="moments-grid">
              <article class="moment-card">
                <a class="moment-card__link" href="<?php echo $story_links[0]; ?>" target="_blank" rel="noopener" title="<?php echo Utils::e(t('basic_info.stories.1.title_attr')); ?>">
                  <img src="assets/images/image7.jpg" alt="<?php echo Utils::e(t('basic_info.stories.1.title')); ?>" />
                  <div class="moment-caption">
                    <h3><?php echo t('basic_info.stories.1.title'); ?></h3>
                    <p><?php echo t('basic_info.stories.1.desc'); ?></p>
                    <span class="moment-card__cta"><?php echo t('basic_info.read_cta'); ?></span>
                  </div>
                </a>
              </article>
              <article class="moment-card">
                <a class="moment-card__link" href="<?php echo $story_links[1]; ?>" target="_blank" rel="noopener" title="<?php echo Utils::e(t('basic_info.stories.2.title_attr')); ?>">
                  <img src="assets/images/image5.jpg" alt="<?php echo Utils::e(t('basic_info.stories.2.title')); ?>" />
                  <div class="moment-caption">
                    <h3><?php echo t('basic_info.stories.2.title'); ?></h3>
                    <p><?php echo t('basic_info.stories.2.desc'); ?></p>
                    <span class="moment-card__cta"><?php echo t('basic_info.read_cta'); ?></span>
                  </div>
                </a>
              </article>
              <article class="moment-card">
                <a class="moment-card__link" href="<?php echo $story_links[2]; ?>" target="_blank" rel="noopener" title="<?php echo Utils::e(t('basic_info.stories.3.title_attr')); ?>">
                  <img src="assets/images/image6.png" alt="<?php echo Utils::e(t('basic_info.stories.3.title')); ?>" />
                  <div class="moment-caption">
                    <h3><?php echo t('basic_info.stories.3.title'); ?></h3>
                    <p><?php echo t('basic_info.stories.3.desc'); ?></p>
                    <span class="moment-card__cta"><?php echo t('basic_info.read_cta'); ?></span>
                  </div>
                </a>
              </article>
              <article class="moment-card">
                <a class="moment-card__link" href="<?php echo $story_links[3]; ?>" target="_blank" rel="noopener" title="<?php echo Utils::e(t('basic_info.stories.4.title_attr')); ?>">
                  <img src="assets/images/image1.png" alt="<?php echo Utils::e(t('basic_info.stories.4.title')); ?>" />
                  <div class="moment-caption">
                    <h3><?php echo t('basic_info.stories.4.title'); ?></h3>
                    <p><?php echo t('basic_info.stories.4.desc'); ?></p>
                    <span class="moment-card__cta"><?php echo t('basic_info.read_cta'); ?></span>
                  </div>
                </a>
              </article>
            </div>
          </div>
        </div>
      </section>
      <!-- /BASIC INFO -->

      <!-- TINY BLOG -->
      
      <!-- /TINY BLOG -->

      <!-- EXTRA INFO -->
      <article class="extra-info">
        <section class="sect1">
          <h3><?php echo t_num($casualties['killed']); ?></h3>
          <p><?php echo t('extra_info.killed'); ?></p>
        </section>
        <section class="sect2">
          <h3><?php echo t_num($casualties['children']); ?></h3>
          <p><?php echo t('extra_info.children'); ?></p>
        </section>
      </article>
      <!-- /EXTRA INFO -->

      <!-- RESOURCES -->
      <?php
        $resources_goal = null;
        foreach ($goals as $goal) {
          if (($goal["deliverable"] ?? "") === "recursos") {
            $resources_goal = $goal;
            break;
          }
        }
      ?>
      <section class="resources" id="recursos">
        <header class="resources__header">
          <span class="eyebrow"><?php echo t('resources.eyebrow'); ?></span>
          <h2><?php echo t('resources.title'); ?></h2>
          <?php if ($resources_goal): ?>
            <span class="resources__badge">
              <span class="resources__badge-dot" aria-hidden="true"></span>
              <?php echo t('resources.badge', ['count' => t_num($resources_goal["signatures"])]); ?>
            </span>
          <?php endif; ?>
          <p><?php echo t('resources.intro'); ?></p>
        </header>

        <?php
        // Enlaces de recursos por idioma (cada idioma apunta a medios/recursos en esa lengua)
        $resource_links_by_lang = [
            'es' => [
                'docs' => [
                    "https://www.rtve.es/play/videos/vivir-y-morir-en-gaza/",
                    "https://www.rtve.es/play/videos/en-portada/gaza-expediente-genocidio/",
                    "https://www.arte.tv/es/videos/129995-000-A/arte-reportaje/",
                ],
                'books' => [
                    "https://www.penguinlibros.com/es/libros-de-historia/11467-libro-la-cuestion-palestina-9788499920108",
                    "https://www.akal.com/libro/historia-de-la-palestina-moderna-3a-ed_53636/",
                    "https://www.buscalibre.es/libro-palestina-cien-anos-de-colonialismo-y-resistencia/9788412619904/p/54521215",
                ],
                'podcasts' => [
                    "https://www.publico.es/podcasts/no-empezo-el-7-de-octubre.html",
                    "https://www.youtube.com/watch?v=7YBs7F7WbO4",
                    "https://www.podiumpodcast.com/podcasts/al-borde-de-una-segunda-nakba/",
                ],
                'boycott' => [
                    "https://bdsmovement.net/es/guia-de-boicot-desinversion-y-sanciones",
                    "https://www.es.rescop.org/que-puedo-hacer/",
                    "https://www.es.rescop.org/productos-a-evitar/",
                    "https://www.unrwa.es/donar/",
                ],
                'news' => [
                    "https://www.aljazeera.com/where/palestine/",
                    "https://972mag.com/",
                    "https://electronicintifada.net/",
                    "https://www.articulo14.org/",
                    "https://www.elsaltodiario.com/",
                ],
            ],
            'en' => [
                'docs' => [
                    "https://www.rtve.es/play/videos/vivir-y-morir-en-gaza/",
                    "https://www.rtve.es/play/videos/en-portada/gaza-expediente-genocidio/",
                    "https://www.arte.tv/es/videos/129995-000-A/arte-reportaje/",
                ],
                'books' => [
                    "https://www.penguinlibros.com/es/libros-de-historia/11467-libro-la-cuestion-palestina-9788499920108",
                    "https://www.akal.com/libro/historia-de-la-palestina-moderna-3a-ed_53636/",
                    "https://www.buscalibre.es/libro-palestina-cien-anos-de-colonialismo-y-resistencia/9788412619904/p/54521215",
                ],
                'podcasts' => [
                    "https://www.publico.es/podcasts/no-empezo-el-7-de-octubre.html",
                    "https://www.youtube.com/watch?v=7YBs7F7WbO4",
                    "https://www.podiumpodcast.com/podcasts/al-borde-de-una-segunda-nakba/",
                ],
                'boycott' => [
                    "https://bdsmovement.net/es/guia-de-boicot-desinversion-y-sanciones",
                    "https://www.es.rescop.org/que-puedo-hacer/",
                    "https://www.es.rescop.org/productos-a-evitar/",
                    "https://www.unrwa.es/donar/",
                ],
                'news' => [
                    "https://www.aljazeera.com/where/palestine/",
                    "https://972mag.com/",
                    "https://electronicintifada.net/",
                    "https://www.articulo14.org/",
                    "https://www.elsaltodiario.com/",
                ],
            ],
            'fr' => [
                'docs' => [
                    "https://www.arte.tv/fr/videos/122719-000-A/dans-gaza/",
                    "https://www.arte.tv/fr/videos/124073-000-A/gaza-l-impossible-journalisme/",
                    "https://boutique.arte.tv/detail/yallah-gaza",
                ],
                'books' => [
                    "https://lafabrique.fr/le-nettoyage-ethnique-de-la-palestine/",
                    "https://5livres.fr/livres-histoire-palestine",
                    "https://www.leslibraires.ca/livres/le-nettoyage-ethnique-de-la-palestine-ilan-pappe-9782924834602.html",
                ],
                'podcasts' => [
                    "https://www.radiofrance.fr/franceculture/podcasts/cultures-monde/a-gaza-les-palestiniens-depossedes-5646351",
                    "https://www.radiofrance.fr/franceculture/podcasts/soft-power/emission-speciale-israel-gaza-8355044",
                    "https://www.radiofrance.fr/sujets/bande-de-gaza",
                ],
                'boycott' => [
                    "https://www.bdsfrance.org/que-boycotter/",
                    "https://www.bdsfrance.org/participez/boycottez/",
                    "https://listebds.fr/",
                    "https://www.unrwa.fr/",
                ],
                'news' => [
                    "https://www.middleeasteye.net/fr",
                    "https://orientxxi.info/",
                    "https://www.france-palestine.org/",
                    "https://www.amnesty.fr/",
                    "https://www.monde-diplomatique.fr/",
                ],
            ],
            'pt' => [
                'docs' => [
                    "https://www.youtube.com/watch?v=i4t_-0NImVM",
                    "https://www.monitordooriente.com/20240703-gaza-vive-documentario-da-al-jazeera-revela-esperanca-entre-as-ruinas/",
                    "https://veja.abril.com.br/mundo/veja-lanca-documentario-exclusivo-sobre-guerra-em-gaza/",
                ],
                'books' => [
                    "https://editorasundermann.com.br/produto/limpeza-etnica-da-palestina-a-brochura/",
                    "https://editoraunesp.com.br/catalogo/9788539302345%2Ca-questao-da-palestina",
                    "https://editorasundermann.com.br/produto/al-nakba-um-estudo-sobre-a-catastrofe-palestina/",
                ],
                'podcasts' => [
                    "https://quatrocincoum.com.br/podcasts/repertorio-451-mhz/gaza-esta-em-toda-parte/",
                    "https://www.youtube.com/playlist?list=PLjRBy9ECDCkEvJUJAcqJeIQHKWOgQEPJj",
                    "https://g1.globo.com/podcast/o-assunto/noticia/2025/05/21/a-ajuda-humanitaria-a-gaza-e-a-pressao-sobre-israel-o-assunto-1471.ghtml",
                ],
                'boycott' => [
                    "https://bdsportugal.org/",
                    "https://www.brasildefato.com.br/2023/10/11/saiba-o-que-e-bds-movimento-que-defende-boicote-e-sancoes-a-israel",
                    "https://www.diariodocentrodomundo.com.br/o-que-e-o-bds-movimento-de-boicote-a-israel-e-como-voce-pode-se-engajar",
                    "https://www.unrwa.org/",
                ],
                'news' => [
                    "https://www.brasildefato.com.br/",
                    "https://www.cartacapital.com.br/",
                    "https://www.monitordooriente.com/",
                    "https://jornalggn.com.br/",
                    "https://operamundi.uol.com.br/",
                ],
            ],
            'ar' => [
                'docs' => [
                    "https://www.ajiunit.com/ar/investigation/%D8%BA%D8%B2%D8%A9/",
                    "https://www.ajiunit.com/ar/article/%D8%B4%D9%87%D8%A7%D8%AF%D8%A7%D8%AA-%D9%85%D9%86-%D8%BA%D8%B2%D8%A9/",
                    "http://doc.aljazeera.net/",
                ],
                'books' => [
                    "https://www.palestine-studies.org/ar/node/1659088",
                    "https://studies.aljazeera.net/ar",
                    "https://pchrgaza.org/ar/category/genocide-on-gaza-ar/",
                ],
                'podcasts' => [
                    "https://www.aljazeera.net/audio/podcasts/gaza",
                    "https://podcast.ps/series/%D8%A8%D9%88%D8%AF%D9%83%D8%A7%D8%B3%D8%AA-%D9%82%D8%B5%D8%B5-%D9%85%D9%86-%D9%88%D8%B9%D9%86-%D8%BA%D8%B2%D8%A9/",
                    "https://www.youtube.com/@podcast_aswat_from_gaza",
                ],
                'boycott' => [
                    "https://bdsmovement.net/ar/Guide-to-BDS-Boycott",
                    "https://bdsmovement.net/ar",
                    "https://www.asianewslb.com/?id=162557&page=article",
                    "https://www.unrwa.org/ar/",
                ],
                'news' => [
                    "https://www.aljazeera.net/",
                    "http://doc.aljazeera.net/",
                    "https://pchrgaza.org/ar/",
                    "https://studies.aljazeera.net/ar",
                    "https://www.palestine-studies.org/ar",
                ],
            ],
        ];
        $resource_links = $resource_links_by_lang[current_lang()] ?? $resource_links_by_lang['es'];
        ?>
        <div class="resources__grid">
          <?php foreach ($resource_links as $block_key => $links): ?>
            <?php $block = t('resources.blocks.' . $block_key); ?>
            <?php if (is_array($block)): ?>
            <article class="resource-block<?php echo $block_key === 'news' ? ' resource-block--wide' : ''; ?>">
              <h3><?php echo $block['title']; ?></h3>
              <ul>
                <?php foreach ($links as $i => $link): ?>
                  <?php $item = $block['items'][$i] ?? null; ?>
                  <?php if (is_array($item) && isset($item['title'], $item['desc'])): ?>
                  <li>
                    <a href="<?php echo $link; ?>" target="_blank" rel="noopener">
                      <strong><?php echo $item['title']; ?></strong>
                      <span><?php echo $item['desc']; ?></span>
                    </a>
                  </li>
                  <?php endif; ?>
                <?php endforeach; ?>
              </ul>
            </article>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </section>
      <!-- /RESOURCES -->

      <!-- DECORATION -->
      <article class="decoration">
        <span><?php echo t('decoration.word'); ?></span>
        <h2>
          <?php echo t('decoration.l1'); ?><br />
          <?php echo t('decoration.l2'); ?><br />
          <?php echo t('decoration.l3'); ?>
        </h2>
      </article>
      <!-- /DECORATION -->

      <!-- SHARE -->
      <section id="contact_share">
        <article id="share_sign" class="share">
          <header class="form-card__header">
            <span class="eyebrow"><?php echo t('share.eyebrow'); ?></span>
            <h2><?php echo t('share.title'); ?></h2>
            <p><?php echo t('share.subtitle'); ?></p>
          </header>
          <form>
            <div class="form-row">
              <div class="share__sign__input-container mail">
                <label for="sign-email"><?php echo t('share.email_label'); ?></label>
                <input
                  id="sign-email"
                  class="input-sign mail"
                  placeholder="<?php echo Utils::e(t('share.email_placeholder')); ?>"
                  type="email"
                  minlength="0"
                />
              </div>
              <div class="share__sign__input-container name">
                <label for="sign-name"><?php echo t('share.name_label'); ?></label>
                <input
                  id="sign-name"
                  class="input-sign name"
                  placeholder="<?php echo Utils::e(t('share.name_placeholder')); ?>"
                  type="text"
                  minlength="0"
                />
              </div>
            </div>

            <a class="btn btn-second to-sign disabled"><?php echo t('share.sign_btn'); ?></a>

            <p class="tos_text">
              <?php echo t('share.tos', [
                  'sign' => t('share.tos_sign'),
                  'terms' => '<a href="' . lang_url('/terminos-y-condiciones') . '">' . t('share.tos_terms') . '</a>',
                  'legal' => '<a href="' . lang_url('/aviso-legal') . '">' . t('share.tos_legal') . '</a>',
                  'privacy' => '<a href="' . lang_url('/politica-de-privacidad') . '">' . t('share.tos_privacy') . '</a>',
              ]); ?>
            </p>
          </form>
          <div class="links">
            <a href="javascript:void(0)" class="btn btn-primary social-networks"
              ><?php echo t('share.share_btn'); ?></a
            >
          </div>
          <div class="share__networks-container hidden">
            <div class="icon icon-fb" onclick="shareOnFacebook()"></div>
            <div class="icon icon-tw" onclick="shareOnTwitter()"></div>
            <div class="icon icon-lk" onclick="shareOnLinkedIn()"></div>
            <div class="icon icon-wh" onclick="shareOnWhatsApp()"></div>
          </div>
        </article>
        <article class="contact">
          <header class="form-card__header">
            <span class="eyebrow"><?php echo t('contact.eyebrow'); ?></span>
            <h2><?php echo t('contact.title'); ?></h2>
            <p><?php echo t('contact.subtitle'); ?></p>
          </header>
          <form>
            <div class="share__sign__input-container mail">
              <label for="contact-email"><?php echo t('contact.email_label'); ?></label>
              <input
                id="contact-email"
                class="input-sign mail-contact"
                placeholder="<?php echo Utils::e(t('contact.email_placeholder')); ?>"
                type="email"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container subject">
              <label for="contact-subject"><?php echo t('contact.subject_label'); ?></label>
              <input
                id="contact-subject"
                class="input-sign subject"
                placeholder="<?php echo Utils::e(t('contact.subject_placeholder')); ?>"
                type="text"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container msg">
              <label for="contact-msg"><?php echo t('contact.msg_label'); ?></label>
              <textarea id="contact-msg" placeholder="<?php echo Utils::e(t('contact.msg_placeholder')); ?>"></textarea>
            </div>
            <div class="website sr-only" aria-hidden="true">
              <label for="contact-website"><?php echo t('contact.honeypot_label'); ?></label>
              <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off" />
            </div>
            <a class="send-email btn btn-second disabled"><?php echo t('contact.send_btn'); ?></a>
          </form>
        </article>
      </section>
      <!-- /SHARE -->

      <!-- SOCIAL -->
      <section id="social" class="social">
        <header class="social__header">
          <span class="social__badge">
            <span class="social__badge-dot" aria-hidden="true"></span>
            <?php echo t('social.badge'); ?>
          </span>
          <h2><?php echo t('social.title'); ?></h2>
          <p><?php echo t('social.intro'); ?></p>
        </header>
        <div class="social__grid">
          <a class="social-card" href="https://www.tiktok.com/@www.freepalestine.es" target="_blank" rel="noopener" title="<?php echo Utils::e(t('social.titles.tiktok')); ?>">
            <span class="social-card__icon social-card__icon--tiktok" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
              </svg>
            </span>
            <span class="social-card__name">TikTok</span>
            <span class="social-card__handle">@www.freepalestine.es</span>
            <span class="social-card__cta"><?php echo t('social.follow'); ?></span>
          </a>
          <a class="social-card" href="https://www.instagram.com/freepalestine.es/?hl=es" target="_blank" rel="noopener" title="<?php echo Utils::e(t('social.titles.instagram')); ?>">
            <span class="social-card__icon social-card__icon--instagram" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
              </svg>
            </span>
            <span class="social-card__name">Instagram</span>
            <span class="social-card__handle">@freepalestine.es</span>
            <span class="social-card__cta"><?php echo t('social.follow'); ?></span>
          </a>
          <a class="social-card" href="https://x.com/wwfreepalestine" target="_blank" rel="noopener" title="<?php echo Utils::e(t('social.titles.x')); ?>">
            <span class="social-card__icon social-card__icon--x" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/>
              </svg>
            </span>
            <span class="social-card__name">X</span>
            <span class="social-card__handle">@wwfreepalestine</span>
            <span class="social-card__cta"><?php echo t('social.follow'); ?></span>
          </a>
          <a class="social-card" href="https://www.threads.com/@freepalestine.es" target="_blank" rel="noopener" title="<?php echo Utils::e(t('social.titles.threads')); ?>">
            <span class="social-card__icon social-card__icon--threads" aria-hidden="true">
              <svg role="img" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.263 11.097c-.03-3.486-1.92-5.586-5.111-5.586-2.13 0-3.922.963-4.863 2.499l2.062 1.438c.535-.843 1.272-1.543 2.628-1.543 1.528 0 2.318.85 2.544 2.431a15 15 0 0 0-2.236-.173c-4.125 0-6.068 1.867-6.068 4.336s1.943 3.99 4.804 3.99c3.139 0 5.013-2.115 5.781-4.735.798.361 1.348 1.204 1.348 2.47 0 3.387-3.907 5.232-7.22 5.232-4.885 0-8.077-3.207-8.077-8.424 0-6.392 4.223-10.487 9.9-10.487 3.808 0 5.69 1.671 6.97 3.914l2.108-1.475C21.44 2.078 18.331 0 13.663 0 6.227 0 1.168 5.277 1.168 12.934c0 7 4.953 11.066 10.856 11.066 4.878 0 9.809-2.846 9.809-7.716 0-2.545-1.46-4.231-3.569-5.187m-6.33 4.855c-1.077 0-2.026-.512-2.026-1.453 0-1.483 1.822-1.934 3.606-1.934.678 0 1.34.045 1.927.173-.422 1.927-1.671 3.215-3.508 3.214Z"/>
              </svg>
            </span>
            <span class="social-card__name">Threads</span>
            <span class="social-card__handle">@freepalestine.es</span>
            <span class="social-card__cta"><?php echo t('social.follow'); ?></span>
          </a>
        </div>
      </section>
      <!-- /SOCIAL -->
    </main>
    <!-- /CONTENT -->

    <!-- FOOTER -->
    <footer>
      <div class="footer-section">
        <h4><?php echo t('footer.brand'); ?></h4>
        <p><?php echo t('footer.desc'); ?></p>
        <p>
          <img class="footer-flag" src="assets/svg/flag-palestine.svg" alt="<?php echo Utils::e(t('footer.flag_alt')); ?>">
        </p>
      </div>
      <div class="footer-section">
        <h4><?php echo t('footer.legal'); ?></h4>
        <a href="<?php echo lang_url('/aviso-legal'); ?>"><?php echo t('footer.legal_aviso'); ?></a>
        <a href="<?php echo lang_url('/politica-de-privacidad'); ?>"><?php echo t('footer.legal_priv'); ?></a>
        <a href="<?php echo lang_url('/terminos-y-condiciones'); ?>"><?php echo t('footer.legal_terms'); ?></a>
      </div>
      <div class="footer-section">
        <h4><?php echo t('footer.credits'); ?></h4>
        <a href="https://data.techforpalestine.org/" target="_blank" rel="noopener"><?php echo t('footer.credits_data'); ?></a>
        <a href="https://freepalestineproject.com/" target="_blank" rel="noopener"><?php echo t('footer.credits_gallery'); ?></a>
        <a href="https://github.com/jedahee/FreePalestine" target="_blank" rel="noopener"><?php echo t('footer.credits_github'); ?></a>
      </div>
    </footer>
    <!-- /FOOTER -->

    <!-- GOAL POPUP: STICKERS -->
    <div class="goal-popup hidden" id="goal-popup" role="dialog" aria-modal="true" aria-labelledby="goal-popup-title">
      <div class="goal-popup__card">
        <button type="button" class="goal-popup__close" data-close="goal-popup" aria-label="<?php echo Utils::e(t('goal_popup.close_aria')); ?>">
          <span aria-hidden="true">&times;</span>
        </button>
        <span class="eyebrow"><?php echo t('goal_popup.eyebrow', ['count' => t_num(500)]); ?></span>
        <h2 id="goal-popup-title"><?php echo t('goal_popup.title'); ?></h2>
        <p class="goal-popup__intro">
          <?php echo t('goal_popup.intro'); ?>
        </p>
        <div class="sticker-grid">
          <img src="assets/stickers/sandia.png" alt="<?php echo Utils::e(t('goal_popup.stickers.sponsor')); ?>" loading="lazy" />
          <img src="assets/stickers/paloma.png" alt="<?php echo Utils::e(t('goal_popup.stickers.zionism')); ?>" loading="lazy" />
          <img src="assets/stickers/bandera.png" alt="<?php echo Utils::e(t('goal_popup.stickers.flag')); ?>" loading="lazy" />
          <img src="assets/stickers/resistencia.png" alt="<?php echo Utils::e(t('goal_popup.stickers.resistencia')); ?>" loading="lazy" />
          <img src="assets/stickers/corazon.png" alt="<?php echo Utils::e(t('goal_popup.stickers.heart')); ?>" loading="lazy" />
          <img src="assets/stickers/megafono.png" alt="<?php echo Utils::e(t('goal_popup.stickers.keffiyeh')); ?>" loading="lazy" />
          <img src="assets/stickers/cupula.png" alt="<?php echo Utils::e(t('goal_popup.stickers.chains')); ?>" loading="lazy" />
          <img src="assets/stickers/libertad.png" alt="<?php echo Utils::e(t('goal_popup.stickers.freedom')); ?>" loading="lazy" />
        </div>
        <a class="btn btn-primary goal-popup__download" href="backend/download_stickers.php">
          <?php echo t('goal_popup.download'); ?>
        </a>
        <p class="goal-popup__note">
          <?php echo t('goal_popup.note', [
              'maker' => '<strong>' . t('goal_popup.note_maker') . '</strong>',
              'ly' => '<strong>' . t('goal_popup.note_ly') . '</strong>',
          ]); ?>
        </p>
      </div>
    </div>
    <!-- /GOAL POPUP -->

    <script>
      window.I18N = <?php echo json_encode(js_i18n(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
  </body>
</html>
