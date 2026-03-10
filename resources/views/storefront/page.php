<?php
ob_start();
?>
<section class="panel">
  <h2><?= htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
  <?php if (!empty($page['content_html'])): ?>
    <div><?= (string) $page['content_html'] ?></div>
  <?php else: ?>
    <p class="muted">Ingen sidtext inlagd ännu.</p>
  <?php endif; ?>
</section>

<section class="trust-grid" aria-label="Hjälplänkar">
  <?php foreach (($infoPages ?? []) as $link): ?>
    <?php if (($link['slug'] ?? '') !== ($page['slug'] ?? '')): ?>
      <article class="trust-item">
        <strong><?= htmlspecialchars((string) $link['label'], ENT_QUOTES, 'UTF-8') ?></strong>
        <p class="muted"><a href="<?= htmlspecialchars((string) $link['url'], ENT_QUOTES, 'UTF-8') ?>">Öppna infosida</a></p>
      </article>
    <?php endif; ?>
  <?php endforeach; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = (string) ($page['meta_title'] ?? $page['title'] ?? 'Informationssida') . ' | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
