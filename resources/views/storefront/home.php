<?php
$hero = $sections['hero'] ?? null;
$intro = $sections['intro'] ?? null;
$info = $sections['info'] ?? null;
$featuredProductsSection = $sections['featured_products'] ?? null;
$featuredCategoriesSection = $sections['featured_categories'] ?? null;

ob_start();
?>
<?php if ($hero !== null): ?>
<section class="panel" style="border-color:#4a2020;background:linear-gradient(135deg,#1b1111,#121217);margin-bottom:.8rem;">
  <h2><?= htmlspecialchars((string) ($hero['title'] ?? 'A-Racing'), ENT_QUOTES, 'UTF-8') ?></h2>
  <?php if (!empty($hero['subtitle'])): ?><p class="muted"><?= htmlspecialchars((string) $hero['subtitle'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (!empty($hero['body_html'])): ?><div><?= (string) $hero['body_html'] ?></div><?php endif; ?>
  <?php if (!empty($hero['button_text']) && !empty($hero['button_url'])): ?><p><a class="btn-primary" href="<?= htmlspecialchars((string) $hero['button_url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $hero['button_text'], ENT_QUOTES, 'UTF-8') ?></a></p><?php endif; ?>
</section>
<?php endif; ?>

<?php if ($intro !== null): ?>
<section class="panel" style="margin-bottom:.8rem;">
  <h3><?= htmlspecialchars((string) ($intro['title'] ?? 'Intro'), ENT_QUOTES, 'UTF-8') ?></h3>
  <?php if (!empty($intro['body_html'])): ?><div><?= (string) $intro['body_html'] ?></div><?php endif; ?>
</section>
<?php endif; ?>

<?php if ($featuredProductsSection !== null): ?>
<section class="panel" style="margin-bottom:.8rem;">
  <h3><?= htmlspecialchars((string) ($featuredProductsSection['title'] ?? 'Utvalda produkter'), ENT_QUOTES, 'UTF-8') ?></h3>
  <?php if (!empty($featuredProductsSection['subtitle'])): ?><p class="muted"><?= htmlspecialchars((string) $featuredProductsSection['subtitle'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <div class="product-grid">
    <?php foreach ($featured_products as $product): ?>
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
    <?php endforeach; ?>
    <?php if ($featured_products === []): ?><p class="muted">Inga utvalda produkter valda ännu.</p><?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($featuredCategoriesSection !== null): ?>
<section class="panel" style="margin-bottom:.8rem;">
  <h3><?= htmlspecialchars((string) ($featuredCategoriesSection['title'] ?? 'Utvalda kategorier'), ENT_QUOTES, 'UTF-8') ?></h3>
  <div class="product-grid">
    <?php foreach ($featured_categories as $category): ?>
      <article class="product-card">
        <h4><a href="/category/<?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></a></h4>
      </article>
    <?php endforeach; ?>
    <?php if ($featured_categories === []): ?><p class="muted">Inga utvalda kategorier valda ännu.</p><?php endif; ?>
  </div>
</section>
<?php endif; ?>


<section class="trust-grid" aria-label="Trygghetsinformation">
  <article class="trust-item"><strong>Snabb leverans</strong><p class="muted">Vi skickar lagervaror snabbt och kommunicerar status tydligt.</p></article>
  <article class="trust-item"><strong>Retur & reklamation</strong><p class="muted">Smidig hantering via tydliga villkor och kontaktväg.</p></article>
  <article class="trust-item"><strong>Motorsportprofil</strong><p class="muted">Specialiserat sortiment för racing och prestanda.</p></article>
  <article class="trust-item"><strong>Behöver du hjälp?</strong><p class="muted">Se <a href="/pages/kontakt">kontakt</a> för snabb support.</p></article>
</section>

<?php if ($info !== null): ?>
<section class="panel">
  <h3><?= htmlspecialchars((string) ($info['title'] ?? 'Info'), ENT_QUOTES, 'UTF-8') ?></h3>
  <?php if (!empty($info['body_html'])): ?><div><?= (string) $info['body_html'] ?></div><?php endif; ?>
</section>
<?php endif; ?>

<?php
$content = (string) ob_get_clean();
$title = 'A-Racing | Start';
require __DIR__ . '/../layouts/storefront.php';
