<?php
ob_start();
?>
<section class="panel">
  <?php if ($category === null): ?>
    <h2>Kategori hittades inte</h2>
  <?php else: ?>
    <p class="muted"><a href="/" style="color:#c8c8cf;">Start</a> / <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></p>
    <h2><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted"><?= (int) ($total ?? 0) ?> träffar</p>
  <?php endif; ?>
</section>

<section class="trust-grid" aria-label="Trygghetsinformation kategori">
  <article class="trust-item"><strong>Frågor om passform?</strong><p class="muted">Kontakta oss via <a href="/pages/kontakt">kontakt</a> innan köp.</p></article>
  <article class="trust-item"><strong>Leveransinformation</strong><p class="muted">Detaljer finns på sidan <a href="/pages/fraktinfo">Fraktinfo</a>.</p></article>
  <article class="trust-item"><strong>Trygg retur</strong><p class="muted">Läs <a href="/pages/retur-reklamation">retur/reklamation</a> för villkor.</p></article>
</section>

<?php if ($category !== null): ?>
  <?php
  $action = '/category/' . urlencode((string) $category['slug']);
  $lockCategory = true;
  require __DIR__ . '/partials/listing-filters.php';
  ?>

  <?php if ($products === []): ?>
    <section class="panel"><p class="muted">Inga produkter matchar dina filter i denna kategori.</p></section>
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
          <?php if ($product['sale_price'] !== null): ?><p><strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p><?php else: ?><p class="muted">Pris visas vid förfrågan – kontakta oss.</p><?php endif; ?>
          <p class="muted">Lager: <?= htmlspecialchars((string) ($product['stock_status'] ?? 'okänd'), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php
$content = (string) ob_get_clean();
$title = 'Kategori | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
