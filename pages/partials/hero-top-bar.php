<?php
/**
 * Barra superior compartida (home + páginas interiores):
 * fecha local + selector de idioma.
 *
 * Requiere i18n cargado (i18n.php). Usa $fecha si ya está definida.
 */
if (!isset($fecha)) {
    try {
        $fecha = (new IntlDateFormatter(current_locale(), IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(time());
    } catch (Exception $e) {
        $fecha = date('d/m/Y');
    }
}
?>
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
