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
<?php
$content = (string) ob_get_clean();
$title = (string) ($page['meta_title'] ?? $page['title'] ?? 'Informationssida') . ' | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
