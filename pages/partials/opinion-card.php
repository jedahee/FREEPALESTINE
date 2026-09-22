<?php
// Tarjeta de opinión de "La Voz Palestina".
// Espera en el contexto: $entry (array de backend/data/opinions.json).
// Se reutiliza en home.php, pages/opinions.php y pages/opinion.php (relacionadas).
$entry = isset($entry) ? (array) $entry : [];
$has_external_image = !empty($entry['image']) && preg_match('#^https?://#i', (string) $entry['image']);
$entry_title = Utils::e($entry['title'] ?? '');
$entry_alt = Utils::e(sprintf('%s — %s', $entry['author'] ?? t('opinions.anonymous'), $entry['title'] ?? ''));
?>
<article class="opinion-card">
  <a class="opinion-card__link" href="<?php echo opinion_url($entry['slug'] ?? ''); ?>">
    <?php if ($has_external_image): ?>
      <div class="opinion-card__media">
        <img
          src="<?php echo Utils::e($entry['image']); ?>"
          alt="<?php echo $entry_alt; ?>"
          loading="lazy"
          decoding="async"
        />
      </div>
    <?php else: ?>
      <div class="opinion-card__media opinion-card__media--fallback" aria-hidden="true"></div>
    <?php endif; ?>

    <div class="opinion-card__content">
      <div class="opinion-card__meta">
        <?php foreach (($entry['categories'] ?? []) as $cat): ?>
          <span class="chip chip--category"><?php echo Utils::e(t_category($cat)); ?></span>
        <?php endforeach; ?>
        <time datetime="<?php echo Utils::e($entry['date'] ?? ''); ?>"><?php echo Utils::e(format_opinion_date($entry['date'] ?? '')); ?></time>
      </div>

      <h2 class="opinion-card__title"><?php echo $entry_title; ?></h2>

      <p class="opinion-card__excerpt"><?php echo Utils::e($entry['excerpt'] ?? ''); ?></p>

      <div class="opinion-card__footer">
        <span class="opinion-card__author">
          <?php echo t('opinions.written_by'); ?>
          <strong><?php echo Utils::e($entry['author'] ?? t('opinions.anonymous')); ?></strong>
        </span>
        <span class="opinion-card__read">
          <?php echo t('opinions.read_opinion'); ?><span aria-hidden="true">&rarr;</span>
        </span>
      </div>
    </div>
  </a>
</article>