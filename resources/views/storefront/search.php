<?php
ob_start();
?>
<section class="panel">
  <h2>Produktsök</h2>
  <p class="muted"><?= (int) ($total ?? 0) ?> träffar</p>
</section>

<?php
$action = '/search';
$lockCategory = false;
require __DIR__ . '/partials/listing-filters.php';
?>


<section class="trust-grid" aria-label="Trygghetsinformation sök">
  <article class="trust-item"><strong>Saknas pris?</strong><p class="muted">Vissa artiklar säljs via förfrågan. Kontakta oss för offert.</p></article>
  <article class="trust-item"><strong>Specialisthjälp</strong><p class="muted">Vi hjälper dig hitta rätt motorsportdel snabbt.</p></article>
</section>

<?php if ($products === []): ?>
  <section class="panel">
    <h3>Inga produkter hittades</h3>
    <p class="muted">Testa att justera sökord, prisintervall eller filter.</p>
  </section>
<?php else: ?>
  <div class="product-grid">
    <?php foreach ($products as $product): ?>
      <article class="product-card">
        <?php if (!empty($product['image_url'])): ?>
          <img class="product-thumb" src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
          <div class="image-placeholder">Ingen bild</div>
        <?php endif; ?>
        <h3><a href="/product/<?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
        <p class="muted">Varumärke: <?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($product['sale_price'] !== null): ?><p><strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p><?php else: ?><p class="muted">Pris visas vid förfrågan – kontakta oss</p><?php endif; ?>
        <p class="muted">Lager: <?= htmlspecialchars((string) ($product['storefront_stock_label'] ?? 'Tillfälligt slut'), ENT_QUOTES, 'UTF-8') ?></p>
          <?php if (!(bool) ($product['is_purchasable'] ?? false)): ?><p class="pill bad">Ej köpbar just nu</p><?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
$title = 'Sök | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
