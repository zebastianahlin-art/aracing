<?php
ob_start();
?>
<section class="panel">
  <?php if ($product === null): ?>
    <h2>Produkt hittades inte</h2>
  <?php else: ?>
    <h2><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p class="muted">Brand: <?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p class="muted">SKU: <?= htmlspecialchars((string) ($product['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= nl2br(htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
    <?php if ($product['sale_price'] !== null): ?>
      <p><strong>Pris: <?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php endif; ?>
    <p class="muted">Lagerstatus: <?= htmlspecialchars((string) ($product['stock_status'] ?? 'okänd'), ENT_QUOTES, 'UTF-8') ?><?= $product['stock_quantity'] !== null ? ' (' . (int) $product['stock_quantity'] . ')' : '' ?></p>

    <?php $canBuy = ($product['stock_status'] ?? '') !== 'out_of_stock' && $product['sale_price'] !== null; ?>
    <?php if ($canBuy): ?>
      <form method="post" action="/cart/items" class="inline-form">
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <label for="qty">Antal</label>
        <input id="qty" type="number" name="quantity" min="1" value="1" style="max-width:90px;">
        <button type="submit" class="btn-primary">Lägg i kundvagn</button>
      </form>
    <?php else: ?>
      <p class="muted">Produkten kan inte köpas just nu (saknar pris eller är slut i lager).</p>
    <?php endif; ?>

    <h3>Attribut</h3>
    <?php if (($product['attributes'] ?? []) === []): ?>
      <p class="muted">Inga attribut registrerade.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($product['attributes'] as $attribute): ?>
          <li><strong><?= htmlspecialchars((string) $attribute['attribute_key'], ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $attribute['attribute_value'], ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h3>Bilder</h3>
    <?php if (($product['images'] ?? []) === []): ?>
      <p class="muted">Inga bilder registrerade.</p>
    <?php else: ?>
      <div class="image-strip">
        <?php foreach ($product['images'] as $image): ?>
          <div class="image-item">
            <img src="<?= htmlspecialchars((string) $image['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($image['alt_text'] ?: $product['name']), ENT_QUOTES, 'UTF-8') ?>">
            <div class="muted"><?= (int) $image['is_primary'] === 1 ? 'Primärbild' : 'Bild' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Produkt | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
