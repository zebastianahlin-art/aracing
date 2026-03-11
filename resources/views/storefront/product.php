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

    <?php $images = $product['images'] ?? []; $primaryImage = $images[0] ?? null; ?>
    <?php if ($primaryImage !== null): ?>
      <img class="product-hero" src="<?= htmlspecialchars((string) $primaryImage['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($primaryImage['alt_text'] ?: $product['name']), ENT_QUOTES, 'UTF-8') ?>">
      <?php if (count($images) > 1): ?>
        <div class="thumb-strip">
          <?php foreach (array_slice($images, 1) as $image): ?>
            <img src="<?= htmlspecialchars((string) $image['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($image['alt_text'] ?: $product['name']), ENT_QUOTES, 'UTF-8') ?>">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="image-placeholder" style="max-width:560px;">Ingen produktbild uppladdad</div>
    <?php endif; ?>

    <p><?= nl2br(htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
    <?php if ($product['sale_price'] !== null): ?>
      <p><strong>Pris: <?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php else: ?>
      <p class="muted">Pris saknas just nu. Skicka prisförfrågan via <a href="/pages/kontakt">kontakt</a>.</p>
    <?php endif; ?>
    <p class="muted">Lagerstatus: <?= htmlspecialchars((string) ($product['storefront_stock_label'] ?? 'Tillfälligt slut'), ENT_QUOTES, 'UTF-8') ?> (<?= (int) ($product['stock_quantity'] ?? 0) ?>)</p>

    <?php
      $summary = is_array($reviewSummary ?? null) ? $reviewSummary : ['review_count' => 0, 'average_rating' => 0];
      $reviewCount = (int) ($summary['review_count'] ?? 0);
      $averageRating = (float) ($summary['average_rating'] ?? 0);
    ?>
    <p><strong>Betyg:</strong> <?= number_format($averageRating, 1, ',', ' ') ?>/5 · <?= $reviewCount ?> recension<?= $reviewCount === 1 ? '' : 'er' ?></p>

    <?php $canBuy = (bool) ($product['is_purchasable'] ?? false) && $product['sale_price'] !== null; ?>
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

    <section class="trust-grid" aria-label="Trygghetsinformation produkt">
      <article class="trust-item"><strong>Snabb kontakt</strong><p class="muted">Fråga om produkten via <a href="/pages/kontakt">kontakt</a>.</p></article>
      <article class="trust-item"><strong>Frakt & retur</strong><p class="muted">Läs <a href="/pages/fraktinfo">fraktinfo</a> och <a href="/pages/retur-reklamation">retur/reklamation</a>.</p></article>
      <article class="trust-item"><strong>Motorsportspecialist</strong><p class="muted">Vi fokuserar på tydlig produktdata för racing.</p></article>
    </section>

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

    <h3>Recensioner</h3>
    <?php if (($reviewMessage ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $reviewMessage, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (($reviewError ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $reviewError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <?php if ($customer !== null): ?>
      <form method="post" action="/product/<?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?>/reviews" class="panel" style="margin:.5rem 0;">
        <h4>Lämna recension</h4>
        <div class="grid-2">
          <div>
            <label for="rating">Betyg (1–5)</label>
            <select id="rating" name="rating" required>
              <option value="">Välj betyg</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= $i ?>/5</option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label for="title">Rubrik (valfri)</label>
            <input id="title" type="text" name="title" maxlength="190" placeholder="Kort rubrik">
          </div>
        </div>
        <label for="review_text">Recension</label>
        <textarea id="review_text" name="review_text" required placeholder="Skriv vad du tyckte om produkten"></textarea>
        <button type="submit" class="btn-primary">Skicka recension</button>
        <p class="muted">Din recension publiceras först efter granskning av admin.</p>
      </form>
    <?php else: ?>
      <p class="muted">Logga in för att lämna en recension.</p>
    <?php endif; ?>

    <?php $reviews = is_array($publicReviews ?? null) ? $publicReviews : []; ?>
    <?php if ($reviews === []): ?>
      <p class="muted">Inga publicerade recensioner ännu.</p>
    <?php else: ?>
      <?php foreach ($reviews as $review): ?>
        <article class="panel" style="margin:.5rem 0;">
          <p><strong><?= (int) $review['rating'] ?>/5</strong> · <?= htmlspecialchars((string) ($review['reviewer_name'] ?? 'Kund'), ENT_QUOTES, 'UTF-8') ?><?php if ((int) ($review['is_verified_purchase'] ?? 0) === 1): ?> · <span class="pill ok">Verifierat köp</span><?php endif; ?></p>
          <?php if (!empty($review['title'])): ?><p><strong><?= htmlspecialchars((string) $review['title'], ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
          <?php if (!empty($review['review_text'])): ?><p><?= nl2br(htmlspecialchars((string) $review['review_text'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
          <p class="muted">Publicerad: <?= htmlspecialchars((string) ($review['published_at'] ?? $review['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Produkt | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
