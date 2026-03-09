<?php
ob_start();
?>
<section class="panel">
  <?php if ($category === null): ?>
    <h2>Kategori hittades inte</h2>
  <?php else: ?>
    <h2>Kategori: <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($products === []): ?>
      <p class="muted">Inga aktiva produkter i denna kategori.</p>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $product): ?>
          <article class="product-card">
            <h3><a href="/product/<?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
            <p class="muted">Brand: <?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="muted">SKU: <?= htmlspecialchars((string) ($product['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($product['sale_price'] !== null): ?><p><strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
            <p class="muted">Lager: <?= htmlspecialchars((string) ($product['stock_status'] ?? 'okänd'), ENT_QUOTES, 'UTF-8') ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kategori | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
