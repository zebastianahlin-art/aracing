<?php
$homepageSections = $homepage_sections ?? [];
$infoPages = $infoPages ?? [];
$seo = $seo ?? null;
ob_start();
?>
<section class="panel" style="margin-bottom:.8rem;">
  <h1>A-Racing</h1>
  <p class="muted">Prestandadelar för racing och gatbil – utvalda produkter och kategorier uppdateras löpande.</p>
</section>

<?php $fitmentStorefront = is_array($fitmentStorefront ?? null) ? $fitmentStorefront : []; ?>
<?php if (($fitmentStorefront['has_active_vehicle'] ?? false) === true): ?>
<section class="panel" style="margin-bottom:.8rem; border-color:#2f7046;">
  <p style="margin:0;"><strong>Du handlar för <?= htmlspecialchars((string) ($fitmentStorefront['active_vehicle_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
  <p class="muted" style="margin:.35rem 0 0;">Passformssignaler i listor och produktsidor utgår från denna bil.</p>
</section>
<?php endif; ?>


<?php $vehicleNavigation = is_array($vehicleNavigation ?? null) ? $vehicleNavigation : []; ?>
<section class="panel" style="margin-bottom:.8rem;">
  <h3><?= htmlspecialchars((string) ($vehicleNavigation['entry_label'] ?? 'Handla till bil'), ENT_QUOTES, 'UTF-8') ?></h3>
  <?php if (($vehicleNavigation['has_active_vehicle'] ?? false) === true): ?>
    <p class="muted">Utforska kategorier för <?= htmlspecialchars((string) ($vehicleNavigation['active_vehicle_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.</p>
    <p><a class="btn-primary" href="<?= htmlspecialchars((string) ($vehicleNavigation['entry_url'] ?? '/shop-by-vehicle'), ENT_QUOTES, 'UTF-8') ?>">Gå till fordonsanpassad kategoriingång</a></p>
    <div class="top-links">
      <?php foreach (($vehicleNavigation['entry_categories'] ?? []) as $entryCategory): ?>
        <a href="<?= htmlspecialchars((string) ($entryCategory['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars((string) ($entryCategory['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php if (is_array($entryCategory['coverage'] ?? null)): ?>
            <small class="muted">(<?= (int) ($entryCategory['coverage']['matched_products'] ?? 0) ?>)</small>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted"><?= htmlspecialchars((string) ($vehicleNavigation['empty_state']['description'] ?? 'Välj bil i YMM-väljaren för att fortsätta.'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><a class="btn-secondary" href="#ymm-selector"><?= htmlspecialchars((string) ($vehicleNavigation['empty_state']['cta_label'] ?? 'Välj bil'), ENT_QUOTES, 'UTF-8') ?></a></p>
  <?php endif; ?>
</section>

<?php foreach ($homepageSections as $section): ?>
  <section class="panel" style="margin-bottom:.8rem;">
    <h3><?= htmlspecialchars((string) ($section['title'] ?? 'Utvalt'), ENT_QUOTES, 'UTF-8') ?></h3>
    <?php if (!empty($section['subtitle'])): ?>
      <p class="muted"><?= htmlspecialchars((string) $section['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="product-grid">
      <?php foreach (($section['items'] ?? []) as $entry): ?>
        <?php if (($entry['item_type'] ?? '') === 'product'): ?>
          <?php $product = $entry['item']; ?>
          <article class="product-card">
            <?php if (!empty($product['image_url'])): ?>
              <img class="product-thumb" src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
              <div class="image-placeholder">Ingen bild</div>
            <?php endif; ?>
            <h4><a href="/product/<?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a></h4>
            <p class="muted">Brand: <?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($product['sale_price'] !== null): ?><p><strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
          </article>
        <?php endif; ?>

        <?php if (($entry['item_type'] ?? '') === 'category'): ?>
          <?php $category = $entry['item']; ?>
          <article class="product-card">
            <h4><a href="/category/<?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></a></h4>
            <p class="muted">Handplockad kategori</p>
          </article>
        <?php endif; ?>
      <?php endforeach; ?>
      <?php if (($section['items'] ?? []) === []): ?><p class="muted">Inga giltiga objekt är kopplade till sektionen ännu.</p><?php endif; ?>
    </div>

    <?php if (!empty($section['cta_label']) && !empty($section['cta_url'])): ?>
      <p style="margin-top:.6rem;"><a class="btn" href="<?= htmlspecialchars((string) $section['cta_url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $section['cta_label'], ENT_QUOTES, 'UTF-8') ?></a></p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>

<section class="trust-grid" aria-label="Trygghetsinformation">
  <article class="trust-item"><strong>Snabb leverans</strong><p class="muted">Vi skickar lagervaror snabbt och kommunicerar status tydligt.</p></article>
  <article class="trust-item"><strong>Retur & reklamation</strong><p class="muted">Smidig hantering via tydliga villkor och kontaktväg.</p></article>
  <article class="trust-item"><strong>Motorsportprofil</strong><p class="muted">Specialiserat sortiment för racing och prestanda.</p></article>
  <article class="trust-item"><strong>Behöver du hjälp?</strong><p class="muted">Se <a href="/pages/kontakt">kontakt</a> för snabb support.</p></article>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'A-Racing | Start';
require __DIR__ . '/../layouts/storefront.php';
