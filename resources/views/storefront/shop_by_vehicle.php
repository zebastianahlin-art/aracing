<?php
$vehicleNavigation = is_array($vehicleNavigation ?? null) ? $vehicleNavigation : [];
$entryCategories = $vehicleNavigation['entry_categories'] ?? [];
ob_start();
?>
<section class="panel" style="margin-bottom:.8rem;">
  <h1>Handla till vald bil</h1>
  <?php if (($vehicleNavigation['has_active_vehicle'] ?? false) === true): ?>
    <p class="muted">Aktiv bil: <strong><?= htmlspecialchars((string) ($vehicleNavigation['active_vehicle_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p class="muted">Välj kategori nedan för att fortsätta med befintlig katalog och passformsfilter.</p>
  <?php else: ?>
    <p class="muted"><?= htmlspecialchars((string) (($vehicleNavigation['empty_state']['title'] ?? 'Välj en bil först')), ENT_QUOTES, 'UTF-8') ?></p>
    <p class="muted"><?= htmlspecialchars((string) (($vehicleNavigation['empty_state']['description'] ?? 'Välj bil i YMM-väljaren ovan.')), ENT_QUOTES, 'UTF-8') ?></p>
    <p><a class="btn-secondary" href="#ymm-selector"><?= htmlspecialchars((string) (($vehicleNavigation['empty_state']['cta_label'] ?? 'Välj bil')), ENT_QUOTES, 'UTF-8') ?></a></p>
  <?php endif; ?>
</section>

<section class="panel" style="margin-bottom:.8rem;">
  <h3>Populära kategorier</h3>
  <div class="product-grid">
    <?php foreach ($entryCategories as $category): ?>
      <article class="product-card">
        <h4><a href="<?= htmlspecialchars((string) ($category['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></h4>
        <?php $coverage = is_array($category['coverage'] ?? null) ? $category['coverage'] : null; ?>
        <?php if ($coverage !== null): ?>
          <p><span class="pill <?= ($coverage['has_matches'] ?? false) ? 'ok' : 'warn' ?>"><?= htmlspecialchars((string) ($coverage['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></p>
          <p class="muted"><?= htmlspecialchars((string) ($coverage['hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else: ?>
          <p class="muted">Katalogvisning med vald bil i kontext.</p>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if ($entryCategories === []): ?>
      <p class="muted">Inga publika kategorier är tillgängliga just nu.</p>
    <?php endif; ?>
  </div>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Handla till vald bil | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
