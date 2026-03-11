<?php
ob_start();
$products = is_array($products ?? null) ? $products : [];
?>
<section class="panel">
  <h2>Mina sparade produkter</h2>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <p class="muted">Här ser du produkter du har sparat. Endast publika/synliga produkter visas.</p>
</section>

<section class="panel" style="margin-top:.8rem;">
  <?php if ($products === []): ?>
    <p class="muted">Du har inga sparade produkter ännu.</p>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $product): ?>
        <article class="product-card">
          <a href="/product/<?= rawurlencode((string) $product['slug']) ?>">
            <?php if (!empty($product['image_url'])): ?>
              <img class="product-thumb" src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>">
            <?php else: ?>
              <div class="image-placeholder">Ingen bild</div>
            <?php endif; ?>
            <h3><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          </a>
          <p class="muted"><?= htmlspecialchars((string) ($product['brand_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
          <?php if (($product['sale_price'] ?? null) !== null): ?>
            <p><strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p>
          <?php else: ?>
            <p class="muted">Pris saknas</p>
          <?php endif; ?>
          <p class="muted">Lagerstatus: <?= htmlspecialchars((string) ($product['stock_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
          <form method="post" action="/wishlist/items/remove">
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="back_to" value="/account/wishlist">
            <button type="submit" class="btn-secondary">Ta bort från sparade</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Mina sparade produkter | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
