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
            <img src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" style="margin-bottom:.6rem;aspect-ratio:4/3;object-fit:cover;">
          <?php endif; ?>
          <h3><a href="/product/<?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
          <p class="muted">Varumärke: <?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
          <?php if ($product['sale_price'] !== null): ?><p><strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p><?php else: ?><p class="muted">Pris visas vid förfrågan</p><?php endif; ?>
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
