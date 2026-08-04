<?php 

require_once 'backend/load_env.php';
require_once 'backend/utils.php';
require_once 'backend/goals.php';

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
$fecha = $dias[date('w')] . ', ' . date('j') . ' de ' . $meses[date('n')-1] . ', ' . date('Y');
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <!-- META KEYS -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta
      name="description"
      content="Descubre la historia y los movimientos actuales de la resistencia palestina. Conoce los líderes, eventos clave y el impacto en la lucha por la autodeterminación y los derechos humanos."
    />
    <meta
      name="keywords"
      content="resistencia palestina, Palestina, autodeterminación, derechos humanos, conflicto israelí-palestino, historia palestina, líderes palestinos, movimientos de resistencia, Gaza, Cisjordania, lucha palestina"
    />
    <meta property="og:site_name" content="Free Palestine">
    <meta property="og:title" content="Free Palestine — Firma por Palestina">
    <meta property="og:description" content="Únete a nuestra campaña de firmas en apoyo al pueblo palestino. Cada firma cuenta para visibilizar el genocidio en Gaza y exigir justicia.">
    <meta property="og:url" content="https://freepalestine.es/">
    <meta property="og:image" content="https://freepalestine.es/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Free Palestine — Firma por la libertad y justicia para Palestina">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_ES">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@freepalestine">
    <meta name="twitter:title" content="Free Palestine — Firma por Palestina">
    <meta name="twitter:description" content="Únete a nuestra campaña de firmas en apoyo al pueblo palestino. Cada firma cuenta para visibilizar el genocidio en Gaza y exigir justicia.">
    <meta name="twitter:image" content="https://freepalestine.es/og-image.jpg">
    <link rel="canonical" href="https://freepalestine.es/">
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
    <title>Free Palestine — Firma por la libertad y justicia para Palestina</title>

    <?php
    $stories = [
        [
            "name" => "Unidad Palestina",
            "description" => "El espíritu palestino se eleva por encima de la adversidad, fuerte y unido.",
            "url" => "https://huelladelsur.ar/2025/05/16/palestina-el-grito-de-resistencia-que-interpela-a-toda-la-humanidad/",
            "image" => "assets/images/image7.jpg"
        ],
        [
            "name" => "Daño colosal",
            "description" => "A pesar de las guerras y la destrucción, la resistencia sigue viva.",
            "url" => "https://elpais.com/internacional/2025-10-02/diarios-y-poemas-desde-el-asedio-a-ciudad-de-gaza.html",
            "image" => "assets/images/image5.jpg"
        ],
        [
            "name" => "Protesta",
            "description" => "Los movimientos pro-palestinos se manifiestan contra la injusticia.",
            "url" => "https://www.bbc.co.uk/news/articles/cw0v8d805ypo",
            "image" => "assets/images/image6.png"
        ],
        [
            "name" => "Homenaje Eterno",
            "description" => "Homenaje a los caídos, cuyo espíritu se mantiene vivo en las historias contadas.",
            "url" => "https://www.resumenlatinoamericano.org/2024/03/13/palestina-desde-gaza-una-historia-de-amor-y-resistencia/",
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
                "url" => $base_url . "/",
                "name" => "Free Palestine",
                "description" => "Campaña de firmas en apoyo al pueblo palestino: visibilizar el genocidio en Gaza y exigir justicia.",
                "inLanguage" => "es",
                "publisher" => ["@id" => $base_url . "/#organization"]
            ],
            [
                "@type" => "Organization",
                "@id" => $base_url . "/#organization",
                "name" => "Free Palestine",
                "url" => $base_url . "/",
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
                "@id" => $base_url . "/#webpage",
                "url" => $base_url . "/",
                "name" => "Free Palestine — Firma por la libertad y justicia para Palestina",
                "description" => "Únete a nuestra campaña de firmas en apoyo al pueblo palestino. Cada firma cuenta para visibilizar el genocidio en Gaza y exigir justicia.",
                "inLanguage" => "es",
                "isPartOf" => ["@id" => $base_url . "/#website"],
                "about" => [
                    "@type" => "CreativeWork",
                    "name" => "El Grito de Palestina",
                    "description" => "Historias de resistencia, de lucha y de supervivencia en medio de la adversidad."
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
                    "name" => "El Grito de Palestina",
                    "description" => "Historias de resistencia, de lucha y de supervivencia en medio de la adversidad.",
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
                        "name" => "Inicio",
                        "item" => $base_url . "/"
                    ],
                    [
                        "@type" => "ListItem",
                        "position" => 2,
                        "name" => "Firma por Palestina",
                        "item" => $base_url . "/#share_sign"
                    ]
                ]
            ]
        ]
    ];

    $json_ld_milestones = [
        "@context" => "https://schema.org",
        "@type" => "ItemList",
        "name" => "Metas de la campaña de firmas",
        "description" => "Firmas recogidas hasta ahora: " . number_format($total_signatures) . ". Metas progresivas para visibilizar la causa palestina.",
        "numberOfItems" => count($goals),
        "itemListElement" => array_map(function ($index, $goal) use ($total_signatures) {
            $unlocked = $total_signatures >= $goal["signatures"];
            return [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => "Meta " . number_format($goal["signatures"]) . ($unlocked ? " · cumplida" : " · por descubrir"),
                "description" => $unlocked ? $goal["goal_txt"] : "Por descubrir"
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
      "name": "Víctimas del genocidio en Gaza",
      "description": "Cifras de palestinos asesinados y niños asesinados en Gaza, actualizadas desde data.techforpalestine.org.",
      "url": "https://data.techforpalestine.org/",
      "inLanguage": "es",
      "creator": {
        "@type": "Organization",
        "name": "Tech for Palestine",
        "url": "https://data.techforpalestine.org/"
      },
      "publisher": {
        "@type": "Organization",
        "name": "Free Palestine",
        "url": "<?php echo $base_url; ?>/"
      },
      "sourceOrganization": {
        "@type": "Organization",
        "name": "Tech for Palestine",
        "url": "https://data.techforpalestine.org/"
      },
      "variableMeasured": [
        {
          "@type": "PropertyValue",
          "name": "Palestinos asesinados",
          "value": <?php echo $casualties['killed']; ?>
        },
        {
          "@type": "PropertyValue",
          "name": "Niños asesinados",
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
      <h2>¡Firma completada!</h2>
      <p>Gracias a ti ya tenemos un total de <strong><?php echo $total_signatures; ?></strong> firmas.</p>
      <p>Apoya el proyecto en redes sociales ✊</p>
      <div class="share nt">
        <div class="notification__share">
          <div class="icon icon-fb" onclick="shareOnFacebook()"></div>
          <div class="icon icon-tw" onclick="shareOnTwitter()"></div>
          <div class="icon icon-lk" onclick="shareOnLinkedIn()"></div>
          <div class="icon icon-wh" onclick="shareOnWhatsApp()"></div>
        </div>
      </div>
      <a class="btn btn-primary">Cerrar</a>
    </div>

    <?php
    } else if (isset($_GET["sign"]) && $_GET["sign"] == "false") {
    ?>
      <div class="notification cancel">
        <h2>Firma cancelada</h2>
        <p>La firma se ha cancelado correctamente. Ahora puedes firmar con el mismo correo y nombre</p>
        <a class="btn btn-primary">Cerrar</a>
      </div>
    <?php
    } else if (isset($_GET["sign"]) && $_GET["sign"] == "error") {
    ?>
      <div class="notification error">
        <h2>Surgió un error</h2>
        <p>No se puede realizar la operación. Por favor, intentelo de nuevo</p>
        <a class="btn btn-primary">Cerrar</a>
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
          <div class="date"><?php echo $fecha; ?></div>
        </nav>
        <h1>RESISTENCIA</h1>
        <p class="hero-subtitle">con la causa palestina</p>
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
          <span class="stat-number"><?php echo $total_signatures; ?></span>
          <span class="stat-label">Firmas recogidas</span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?php echo count($goals); ?></span>
          <span class="stat-label">Metas</span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?php echo $next_goal_pct; ?>%</span>
          <span class="stat-label">Hacia la siguiente meta</span>
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
                <span class="milestone-count"><?php echo number_format($goal["signatures"]); ?></span>
                <?php if (!$is_locked): ?>
                  <p><?php echo $goal["goal_txt"]; ?></p>
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
                      <?php echo $goal["deliverable"] === "stickers" ? "Descargar stickers" : "Ver recursos"; ?>
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

      <a class="btn btn-hero" href="#share_sign">Firmar ahora</a>
    </header>
    <!-- /HERO HEADER -->

    <!-- CONTENT -->
    <main>

      <!-- BASIC INFO -->
      <section class="basic-info">
        <header class="basic-info__header">
          <span class="eyebrow">Historias de resistencia</span>
          <h2>El Grito de Palestina</h2>
        </header>

        <div class="basic-info__body">
          <div class="basic-info__narrative">
            <blockquote class="basic-info__lead">
              <span class="lead-mark">“</span>
              En cada calle y cada casa en Palestina hay una historia que contar.
              Historias de resistencia, de lucha y de supervivencia en medio de la adversidad.
            </blockquote>
            <div class="basic-info__paragraphs">
              <p>
                Estos relatos personales de la vida en tiempos de guerra y
                genocidios son el testimonio apasionado de la continuidad de la
                vida palestina.
              </p>
              <p>
                No son solo números o estadísticas en un informe, sino vidas
                vivas, brillando a pesar de la oscuridad.
              </p>
              <p>
                En los mercados bulliciosos, cada sonrisa y gesto amable es un
                acto de resistencia. Las tradiciones transmitidas unen el pasado
                con el presente, floreciendo incluso en medio de la opresión.
              </p>
              <p>
                Bajo el cielo estrellado, los jóvenes sueñan con paz y justicia,
                reflejando una esperanza inquebrantable. Sus lágrimas y risas
                crean una narrativa de humanidad y dignidad que no puede ser
                extinguida.
              </p>
            </div>
          </div>

          <div class="basic-info__moments">
            <div class="moments-grid">
              <article class="moment-card">
                <a class="moment-card__link" href="https://huelladelsur.ar/2025/05/16/palestina-el-grito-de-resistencia-que-interpela-a-toda-la-humanidad/" target="_blank" rel="noopener" title="Leer: el grito de resistencia">
                  <img src="assets/images/image7.jpg" alt="Unidad Palestina" />
                  <div class="moment-caption">
                    <h3>Unidad Palestina</h3>
                    <p>El espíritu palestino se eleva por encima de la adversidad, fuerte y unido.</p>
                    <span class="moment-card__cta">Leer historia</span>
                  </div>
                </a>
              </article>
              <article class="moment-card">
                <a class="moment-card__link" href="https://www.aljazeera.com/where/palestine/" target="_blank" rel="noopener" title="Leer: Palestina en Al Jazeera">
                  <img src="assets/images/image5.jpg" alt="Daño colosal" />
                  <div class="moment-caption">
                    <h3>Daño colosal</h3>
                    <p>A pesar de las guerras y la destrucción, la resistencia sigue viva.</p>
                    <span class="moment-card__cta">Leer historia</span>
                  </div>
                </a>
              </article>
              <article class="moment-card">
                <a class="moment-card__link" href="https://www.bbc.co.uk/news/articles/cw0v8d805ypo" target="_blank" rel="noopener" title="Leer: las protestas pro-palestinas">
                  <img src="assets/images/image6.png" alt="Protesta" />
                  <div class="moment-caption">
                    <h3>Protesta</h3>
                    <p>Los movimientos pro-palestinos se manifiestan contra la injusticia.</p>
                    <span class="moment-card__cta">Leer historia</span>
                  </div>
                </a>
              </article>
              <article class="moment-card">
                <a class="moment-card__link" href="https://www.resumenlatinoamericano.org/2024/03/13/palestina-desde-gaza-una-historia-de-amor-y-resistencia/" target="_blank" rel="noopener" title="Leer: homenaje desde Gaza">
                  <img src="assets/images/image1.png" alt="Homenaje Eterno" />
                  <div class="moment-caption">
                    <h3>Homenaje Eterno</h3>
                    <p>Homenaje a los caídos, cuyo espíritu se mantiene vivo en las historias contadas.</p>
                    <span class="moment-card__cta">Leer historia</span>
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
          <h3><?php echo number_format($casualties['killed']); ?></h3>
          <p>Palestinos asesinados</p>
        </section>
        <section class="sect2">
          <h3><?php echo number_format($casualties['children']); ?></h3>
          <p>Niños asesinados</p>
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
          <span class="eyebrow">Recursos</span>
          <h2>Para entender y actuar</h2>
          <?php if ($resources_goal): ?>
            <span class="resources__badge">
              <span class="resources__badge-dot" aria-hidden="true"></span>
              NUEVO · Desbloqueado con la meta de <?php echo number_format($resources_goal["signatures"]); ?> firmas
            </span>
          <?php endif; ?>
          <p>
            Documentales, libros, podcasts, guías de boicot y medios de análisis
            para informarte, sensibilizar y apoyar la causa palestina.
          </p>
        </header>

        <div class="resources__grid">
          <article class="resource-block">
            <h3>Documentales</h3>
            <ul>
              <li>
                <a href="https://www.rtve.es/play/videos/vivir-y-morir-en-gaza/" target="_blank" rel="noopener">
                  <strong>Vivir y morir en Gaza</strong>
                  <span>Almudena Ariza retrata la vida diaria de los gazatíes bajo el bloqueo y la guerra. RTVE Play.</span>
                </a>
              </li>
              <li>
                <a href="https://www.rtve.es/play/videos/en-portada/gaza-expediente-genocidio/" target="_blank" rel="noopener">
                  <strong>Gaza: expediente genocidio</strong>
                  <span>Investigación del programa En Portada sobre los crímenes contra la humanidad en Gaza. RTVE Play.</span>
                </a>
              </li>
              <li>
                <a href="https://www.arte.tv/es/videos/129995-000-A/arte-reportaje/" target="_blank" rel="noopener">
                  <strong>Gaza: en busca de los desaparecidos</strong>
                  <span>Reportaje de ARTE sobre las personas enterradas bajo los escombros sin poder ser identificadas.</span>
                </a>
              </li>
            </ul>
          </article>

          <article class="resource-block">
            <h3>Libros</h3>
            <ul>
              <li>
                <a href="https://www.penguinlibros.com/es/libros-de-historia/11467-libro-la-cuestion-palestina-9788499920108" target="_blank" rel="noopener">
                  <strong>La cuestión palestina</strong>
                  <span>Edward Said. El ensayo fundacional desde la perspectiva palestina.</span>
                </a>
              </li>
              <li>
                <a href="https://www.akal.com/libro/historia-de-la-palestina-moderna-3a-ed_53636/" target="_blank" rel="noopener">
                  <strong>Historia de la Palestina moderna</strong>
                  <span>Ilan Pappé. Del dominio otomano a la Palestina ocupada.</span>
                </a>
              </li>
              <li>
                <a href="https://www.buscalibre.es/libro-palestina-cien-anos-de-colonialismo-y-resistencia/9788412619904/p/54521215" target="_blank" rel="noopener">
                  <strong>Palestina: cien años de colonialismo y resistencia</strong>
                  <span>Rashid Khalidi. La historia de referencia contada por una familia palestina.</span>
                </a>
              </li>
            </ul>
          </article>

          <article class="resource-block">
            <h3>Podcasts</h3>
            <ul>
              <li>
                <a href="https://www.publico.es/internacional/escucha-episodio-8-podcast-empezo-7-octubre-miquel-ramos.html" target="_blank" rel="noopener">
                  <strong>No empezó el 7 de octubre</strong>
                  <span>Podcast de Público y Mundubat que explica el contexto histórico que lleva a la Nakba actual.</span>
                </a>
              </li>
              <li>
                <a href="https://inshallah.es/" target="_blank" rel="noopener">
                  <strong>Inshallah: un viaje a Palestina</strong>
                  <span>Podcast de UNRWA España con testimonios de refugiados y refugiadas palestinas.</span>
                </a>
              </li>
              <li>
                <a href="https://www.podiumpodcast.com/podcasts/punto-de-fuga-playser-em/episodio/3715142/" target="_blank" rel="noopener">
                  <strong>Al borde de una segunda Nakba</strong>
                  <span>Análisis de Punto de Fuga (Podium) sobre el desplazamiento forzoso y la resistencia palestina.</span>
                </a>
              </li>
            </ul>
          </article>

          <article class="resource-block">
            <h3>Cómo ayudar · Boicot</h3>
            <ul>
              <li>
                <a href="https://bdsmovement.net/es/Guide-to-BDS-Boycott" target="_blank" rel="noopener">
                  <strong>Guía del BDS</strong>
                  <span>Cómo boicotear, desinvertir y sancionar para presionar a Israel.</span>
                </a>
              </li>
              <li>
                <a href="https://porpalestina.org/boicot_israel/" target="_blank" rel="noopener">
                  <strong>Boicot de consumo</strong>
                  <span>Marcas y productos a evitar y campañas activas.</span>
                </a>
              </li>
              <li>
                <a href="https://rescop.org/campanas/bds/boicot-economico/productos-a-evitar/" target="_blank" rel="noopener">
                  <strong>Productos a evitar</strong>
                  <span>Guía de RESCOP para la lista de la compra.</span>
                </a>
              </li>
              <li>
                <a href="https://www.unrwa.es/" target="_blank" rel="noopener">
                  <strong>UNRWA</strong>
                  <span>Agencia de la ONU que sostiene a millones de refugiados palestinos.</span>
                </a>
              </li>
            </ul>
          </article>

          <article class="resource-block resource-block--wide">
            <h3>Noticias y análisis</h3>
            <ul>
              <li>
                <a href="https://www.aljazeera.com/" target="_blank" rel="noopener">
                  <strong>Al Jazeera</strong>
                  <span>Cobertura directa desde Gaza y análisis regional.</span>
                </a>
              </li>
              <li>
                <a href="https://www.972mag.com/" target="_blank" rel="noopener">
                  <strong>+972 Magazine</strong>
                  <span>Periodismo independiente israelí y palestino.</span>
                </a>
              </li>
              <li>
                <a href="https://electronicintifada.net/" target="_blank" rel="noopener">
                  <strong>The Electronic Intifada</strong>
                  <span>Voces palestinas en primera persona.</span>
                </a>
              </li>
              <li>
                <a href="https://www.articulo14.es/" target="_blank" rel="noopener">
                  <strong>Artículo 14</strong>
                  <span>Testimonios y periodismo de investigación en español.</span>
                </a>
              </li>
              <li>
                <a href="https://www.elsaltodiario.com/" target="_blank" rel="noopener">
                  <strong>El Salto</strong>
                  <span>Análisis en profundidad sobre Gaza y el contexto global.</span>
                </a>
              </li>
            </ul>
          </article>
        </div>
      </section>
      <!-- /RESOURCES -->

      <!-- DECORATION -->
      <article class="decoration">
        <span>Firma</span>
        <h2>
          Únete a nosotros y<br />
          firma como usuario<br />
          anónimo.
        </h2>
      </article>
      <!-- /DECORATION -->

      <!-- SHARE -->
      <section id="contact_share">
        <article id="share_sign" class="share">
          <header class="form-card__header">
            <span class="eyebrow">Únete</span>
            <h2>Haz que se escuche tu voz</h2>
            <p>Firma por la libertad y justicia para Palestina.</p>
          </header>
          <form>
            <div class="form-row">
              <div class="share__sign__input-container mail">
                <label for="sign-email">Correo electrónico</label>
                <input
                  id="sign-email"
                  class="input-sign mail"
                  placeholder="tucorreo@ejemplo.com"
                  type="email"
                  minlength="0"
                />
              </div>
              <div class="share__sign__input-container name">
                <label for="sign-name">Nombre completo</label>
                <input
                  id="sign-name"
                  class="input-sign name"
                  placeholder="Nombre y apellidos"
                  type="text"
                  minlength="0"
                />
              </div>
            </div>

            <a class="btn btn-second to-sign disabled">Firmar aquí</a>

            <p class="tos_text">
                Al pulsar el botón "Firmar", usted confirma que ha leído y acepta los <a href="<?php echo Utils::get_base_url() . '/terminos-y-condiciones'; ?>">Términos y Condiciones</a>, el <a href="<?php echo Utils::get_base_url() . '/aviso-legal'; ?>">Aviso Legal</a> y la <a href="<?php echo Utils::get_base_url() . '/politica-de-privacidad'; ?>">Política de Privacidad</a>.
            </p>
          </form>
          <div class="links">
            <a href="javascript:void(0)" class="btn btn-primary social-networks"
              >Comparte en redes sociales</a
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
            <span class="eyebrow">Contacto</span>
            <h2>¡Contáctame!</h2>
            <p>Envía un mensaje y te responderé lo antes posible.</p>
          </header>
          <form>
            <div class="share__sign__input-container mail">
              <label for="contact-email">Correo electrónico</label>
              <input
                id="contact-email"
                class="input-sign mail-contact"
                placeholder="tucorreo@ejemplo.com"
                type="email"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container subject">
              <label for="contact-subject">Asunto</label>
              <input
                id="contact-subject"
                class="input-sign subject"
                placeholder="Asunto del mensaje"
                type="text"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container msg">
              <label for="contact-msg">Mensaje</label>
              <textarea id="contact-msg" placeholder="Escribe tu mensaje"></textarea>
            </div>
            <div class="website sr-only" aria-hidden="true">
              <label for="contact-website">No rellenes este campo</label>
              <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off" />
            </div>
            <a class="send-email btn btn-second disabled">Enviar mensaje</a>
          </form>
        </article>
      </section>
      <!-- /SHARE -->
    </main>
    <!-- /CONTENT -->

    <!-- FOOTER -->
    <footer>
      <div class="footer-section">
        <h4>Free Palestine</h4>
        <p>Plataforma de firmas en apoyo al pueblo palestino.</p>
        <p>
          <img class="footer-flag" src="assets/svg/flag-palestine.svg" alt="Palestina">
        </p>
      </div>
      <div class="footer-section">
        <h4>Legal</h4>
        <a href="<?php echo Utils::get_base_url() . '/aviso-legal'; ?>">Aviso legal</a>
        <a href="<?php echo Utils::get_base_url() . '/politica-de-privacidad'; ?>">Política de privacidad</a>
        <a href="<?php echo Utils::get_base_url() . '/terminos-y-condiciones'; ?>">Términos y condiciones</a>
      </div>
      <div class="footer-section">
        <h4>Créditos</h4>
        <a href="https://data.techforpalestine.org/" target="_blank" rel="noopener">Datos: techforpalestine.org</a>
        <a href="https://freepalestineproject.com/" target="_blank" rel="noopener">Galería: freepalestineproject.com</a>
        <a href="https://github.com/jedahee/FreePalestine" target="_blank" rel="noopener">Contribuye en GitHub</a>
      </div>
    </footer>
    <!-- /FOOTER -->

    <!-- GOAL POPUP: STICKERS -->
    <div class="goal-popup hidden" id="goal-popup" role="dialog" aria-modal="true" aria-labelledby="goal-popup-title">
      <div class="goal-popup__card">
        <button type="button" class="goal-popup__close" data-close="goal-popup" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
        <span class="eyebrow">Meta de 500 firmas · cumplida</span>
        <h2 id="goal-popup-title">Pack de stickers</h2>
        <p class="goal-popup__intro">
          Descarga el pack y compártelo en WhatsApp para visibilizar la causa palestina.
        </p>
        <div class="sticker-grid">
          <img src="assets/stickers/sandia.png" alt="Sticker patrocinador: all my hommies use freepalestine.es" loading="lazy" />
          <img src="assets/stickers/paloma.png" alt="Sticker F*ck Zionism, no al sionismo" loading="lazy" />
          <img src="assets/stickers/bandera.png" alt="Sticker de la bandera de Palestina" loading="lazy" />
          <img src="assets/stickers/resistencia.png" alt="Sticker RESISTENCIA" loading="lazy" />
          <img src="assets/stickers/corazon.png" alt="Sticker Free Palestine" loading="lazy" />
          <img src="assets/stickers/megafono.png" alt="Sticker de keffiyeh palestino" loading="lazy" />
          <img src="assets/stickers/cupula.png" alt="Sticker de cadenas rotas: rompelas" loading="lazy" />
          <img src="assets/stickers/libertad.png" alt="Sticker Libertad" loading="lazy" />
        </div>
        <a class="btn btn-primary goal-popup__download" href="backend/download_stickers.php">
          Descargar pack (ZIP)
        </a>
        <p class="goal-popup__note">
          Para usarlos en WhatsApp: instala la app gratuita <strong>Sticker Maker</strong> o
          <strong>Sticker.ly</strong>, importa los PNG y añádelos a tu WhatsApp.
        </p>
      </div>
    </div>
    <!-- /GOAL POPUP -->
  </body>
</html>
