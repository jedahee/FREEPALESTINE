<?php 
require_once 'backend/load_env.php';
require_once 'backend/utils.php';
require_once 'backend/goals.php';

// Firmas totales
$total_signatures = count(Utils::readJsonFile('backend/' . getenv('FILENAME_JSON')));
$total_signatures_aux = count(Utils::readJsonFile('backend/' . getenv('FILENAME_JSON')));
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <!-- META KEYS -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <meta http-equiv="X-UA-Compatible" content="IE=7" />
    <meta
      name="description"
      content="Descubre la historia y los movimientos actuales de la resistencia palestina. Conoce los líderes, eventos clave y el impacto en la lucha por la autodeterminación y los derechos humanos."
    />
    <meta
      name="keywords"
      content="resistencia palestina, Palestina, autodeterminación, derechos humanos, conflicto israelí-palestino, historia palestina, líderes palestinos, movimientos de resistencia, Gaza, Cisjordania, lucha palestina"
    />
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
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />

    <!-- /STYLES -->

    <!-- SCRIPT -->
    
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <!-- SLIDER SWIPER CDN -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <!-- AOS LIBRARY -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>

    <script src="main.js"></script>
    <!-- /SCRIPT -->
    
    <title>FREE PALESTINE</title>
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
    
    <!-- HEADER -->
    <header>
      <h1 data-aos-duration="1000" data-aos="zoom-out-left">RESISTENCIA</h1>

      <nav data-aos-duration="1000" data-aos="zoom-out-left">
        <div class="signatures-total">
          <p>Firmas recogidas: <strong><?php echo $total_signatures; ?></strong></p>
        </div>
        <div class="date">Saturday, July 6, 2024</div>
      </nav>

      <div data-aos-duration="1000" data-aos="zoom-out-right" class="signature-line">
        <h2>Objetivos</h2>
        <p>Cada firma es importante para cumplir las metas establecidas</p>
        <div class="arrows">
          
        </div>
        <div class="points">

          <?php
            foreach($goals as $index=>$goal) {
              ?>
              <section class="point <?php echo $index > 0 && !$is_last_goal_success && $index > $last_goal_index + 3 ? 'hidden' : ''; ?> <?php echo $total_signatures >= $goal["signatures"] ? 'success' : ''; ?>">
                <?php $sign_word = $goal["signatures"] > 1 ? 'FIRMAS' : 'FIRMA'; ?>
                <h3><?php echo $goal["signatures"] . " " . $sign_word; ?></h3>
                <p><?php echo $index > 0 && !$is_last_goal_success && $index > $last_goal_index + 2  ? 'XXX XX XXX XX XXX XX XXX XX' : $goal["goal_txt"]; ?></p>
                
                <?php
                    if ($index > 0 && ($is_last_goal_success || $index < $last_goal_index + 3)) {
                      
                      if ($total_signatures >= $goal["signatures"])
                        $percent = round((100 * 100) / 185, 2);
                      else{
                        $signatures_goal = isset($goals[$last_goal_index]) ? $goal["signatures"] - $goals[$last_goal_index]["signatures"] : $goal["signatures"];
                        $percent = round(((round(($total_signatures_aux * 100) / $signatures_goal, 2)) * 100) / 185, 2);
                      }

                      if ($index > 0 && $is_last_goal_success)
                        echo '<div class="line progress" style="width: '. $percent .'%;"></div>';
                      $total_signatures_aux = $total_signatures - $goal["signatures"];
                      echo '<div class="line"></div>';

                    } else if ($index > 0) {
                      echo '<div class="line"></div>';
                    }

                  ?>
                
              </section>
              <?php
              $is_last_goal_success = $total_signatures >= $goal["signatures"] ? true : false; 
              $last_goal_index = $total_signatures >= $goal["signatures"] ? $index : null; 
            }
          ?>
        </div>
        <a data-aos-duration="500" data-aos="zoom-out-down" class="btn" href="#share_sign">Firmar</a>
      </div>
    </header>
    <!-- /HEADER -->

    <!-- CONTENT -->
    <main>
      <!-- BASIC INFO -->
      <article class="basic-info">
        <div>
          <h2 data-aos-duration="1000" data-aos="zoom-out-down">El Grito de Palestina</h2>
          <section data-aos-duration="1000" data-aos="zoom-out-right">
            <div>
              <p>
                En cada calle y cada casa en Palestina, hay una historia qué contar.
                Historias de resistencia, de lucha y de supervivencia en medio de la
                adversidad.
              </p>
              <p>
                Estos relatos personales de la vida en tiempos de guerra y
                genocidios son el testimonio apasionado de la continuidad de la vida
                palestina.
              </p>
              <p>
                No son sólo números o estadísticas en un informe, sino vidas vivas,
                brillando a pesar de la oscuridad.
              </p>
              <p>
                En los mercados bulliciosos, cada sonrisa y gesto amable es un acto de resistencia, manteniendo viva la esencia de Palestina. Las tradiciones transmitidas unen el pasado con el presente, floreciendo incluso en medio de la opresión.
              </p>
              <p>
                Bajo el cielo estrellado, los jóvenes sueñan con paz y justicia, reflejando una esperanza inquebrantable. Sus lágrimas y risas crean una narrativa de humanidad y dignidad que no puede ser extinguida.
              </p>
            </div>
          </section>
        </div>
        <div class="swiper" data-aos-duration="1000" data-aos="zoom-out-left">
          <div class="swiper-wrapper">
            <section class="swiper-slide">
              <img src="assets/images/image7.jpg" alt="Unidad Palestina" />
              <div>
                <h3>Unidad Palestina</h3>
                <p>
                  El espíritu palestino se eleva por encima de la adversidad, fuerte
                  y unido.
                </p>
              </div>
            </section>
            <section class="swiper-slide">
              <img src="assets/images/image5.jpg" alt="Daño colosal" />
              <div>
                <h3>Daño colosal</h3>
                <p>
                  A pesar de las guerras y la destrucción, la resistencia sigue
                  viva.
                </p>
              </div>
            </section>
            <section class="swiper-slide">
              <img src="assets/images/image6.png" alt="Protesta" />
              <div>
                <h3>Protesta</h3>
                <p>
                  Los movimientos pro-palestinos se manifiestan contra la
                  injusticia.
                </p>
              </div>
            </section>
            <section class="swiper-slide">
              <img src="assets/images/image1.png" alt="Homenaje Eterno" />
              <div>
                <h3>Homenaje Eterno</h3>
                <p>
                  Homenaje a los caídos, cuyo espíritu se mantiene vivo en las
                  historias contadas.
                </p>
              </div>
            </section>
          </div>
          <div class="swiper-pagination"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
        </div>
      </article>
      <!-- /BASIC INFO -->

      <!-- TINY BLOG -->
      
      <!-- /TINY BLOG -->

      <!-- EXTRA INFO -->
      <article class="extra-info">
        <section class="sect1" data-aos-duration="1000" data-aos="zoom-out-left">
          <h3>
            39.125<!-- https://data.techforpalestine.org/api/v3/summary.json gaza/killed/total-->
          </h3>
          <p>Palestinos asesinados</p>
        </section>

        <img class="img1" src="assets/images/image4.jpg" alt="Fondo 1" data-aos-duration="1000" data-aos="zoom-out-right" />

        <img class="img2" src="assets/images/image3.jpg" alt="Fondo 2" data-aos-duration="1000" data-aos="zoom-out-left" />

        <section class="sect2" data-aos-duration="1000" data-aos="zoom-out-right">
          <h3>
            18.456<!-- https://data.techforpalestine.org/api/v3/summary.json gaza/killed/children-->
          </h3>
          <p>Niños asesinados</p>
        </section>
      </article>
      <!-- /EXTRA INFO -->

      <!-- DECORATION -->
      <article class="decoration">
        <span data-aos-duration="1000" data-aos="zoom-out-down">Firma</span>
        <h2 data-aos-duration="1000" data-aos="zoom-out-right">
          Únete a nosotros y<br />
          firma como usuario<br />
          anónimo.
        </h2>
        <div data-aos-duration="1000" data-aos="zoom-out-left" class="background-img flag-palestine"></div>
      </article>
      <!-- /DECORATION -->

      <!-- SHARE -->
      <section id="contact_share">
        <article id="share_sign" class="share">
          <h2 data-aos-duration="1000" data-aos="zoom-out-down">Haz que se escuche <br />tu voz</h2>
          <form data-aos-duration="1000" data-aos="zoom-out-right">
            <div class="share__sign__input-container mail">
              <input
                class="input-sign mail"
                placeholder="Nombre del correo electrónico"
                type="email"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container name">
              <input
                class="input-sign name"
                placeholder="Nombre completo"
                type="text"
                minlength="0"
              />
            </div>
            <a class="btn btn-second to-sign disabled">Firmar aquí</a>
          </form>
          <div class="links" data-aos-duration="1000" data-aos="zoom-out-right">
            <a href="#" class="btn btn-primary social-networks"
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
          <h2 data-aos-duration="1000" data-aos="zoom-out-down">¡Contáctame!</h2>
          <p data-aos-duration="1000" data-aos="zoom-out-down">Envia un mensaje rellenando el siguiente formulario:</p>
          <form data-aos-duration="1000" data-aos="zoom-out-left">
            <div class="share__sign__input-container mail">
              <input
                class="input-sign mail-contact"
                placeholder="Nombre del correo electrónico"
                type="email"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container subject">
              <input
                class="input-sign subject"
                placeholder="Asunto"
                type="text"
                minlength="0"
              />
            </div>
            <div class="share__sign__input-container msg">
              <textarea placeholder="Mensaje"></textarea>
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
      <div class="copy">
        Datos recogidos de
        <a href="https://data.techforpalestine.org/"
          >https://data.techforpalestine.org/</a
        >
        <br />
        Galería de imágenes usada:
        <a href="https://freepalestineproject.com/"
          >https://freepalestineproject.com/</a
        >
        <br />
        Contribuye con <a href="https://github.com/jedahee/FreePalestine">FREE PALESTINE</a>

        <div class="legal">
          <a target="_blank" href="<?php echo Utils::get_base_url() . '/aviso-legal'; ?>">Aviso legal</a>
          <a target="_blank" href="<?php echo Utils::get_base_url() . '/politica-de-privacidad'; ?>">Política de privacidad</a>
          <a target="_blank" href="<?php echo Utils::get_base_url() . '/terminos-y-condiciones'; ?>">Términos y condiciones</a>
        </div>

  </div>
    </footer>
    <!-- /FOOTER -->
  </body>
</html>
