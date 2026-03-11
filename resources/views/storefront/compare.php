<?php
ob_start();
$products = is_array($products ?? null) ? $products : [];
$compareCount = (int) ($compareCount ?? count($products));
$maxCompareItems = (int) ($maxCompareItems ?? 4);
?>
<section class="panel">
  <h2>Jämför produkter</h2>
  <p class="muted">Jämför upp till <?= $maxCompareItems ?> produkter sida vid sida.</p>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</section>

<?php if ($products === []): ?>
  <section class="panel">
    <h3>Din jämförelselista är tom</h3>
    <p class="muted">Lägg till produkter från en produktsida för att börja jämföra.</p>
    <a class="btn-secondary" href="/search">Till katalogen</a>
  </section>
<?php else: ?>
  <?php if ($compareCount < 2): ?>
    <section class="panel">
      <p class="muted">Du har lagt till 1 produkt. Lägg gärna till minst en till för bättre jämförelse.</p>
    </section>
  <?php endif; ?>

  <section class="panel" style="overflow:auto;">
    <table class="table">
      <tbody>
        <tr>
          <th>Produkt</th>
          <?php foreach ($products as $product): ?>
            <td>
              <a href="/product/<?= htmlspecialchars((string) $product['slug'], ENT_QUOTES, 'UTF-8') ?>"><strong><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></strong></a>
            </td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Bild</th>
          <?php foreach ($products as $product): ?>
            <td>
              <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars((string) $product['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?>" style="width:150px;height:112px;object-fit:cover;">
              <?php else: ?>
                <span class="muted">Saknas</span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Pris</th>
          <?php foreach ($products as $product): ?>
            <td>
              <?php if ($product['sale_price'] !== null): ?>
                <strong><?= htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong>
              <?php else: ?>
                <span class="muted">Saknas</span>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Varumärke</th>
          <?php foreach ($products as $product): ?>
            <td><?= htmlspecialchars((string) ($product['brand_name'] ?? 'Saknas'), ENT_QUOTES, 'UTF-8') ?></td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Lager / köpbarhet</th>
          <?php foreach ($products as $product): ?>
            <td>
              <?= htmlspecialchars((string) ($product['storefront_stock_label'] ?? 'Tillfälligt slut'), ENT_QUOTES, 'UTF-8') ?>
              <?php if (!(bool) ($product['is_purchasable'] ?? false)): ?>
                <div class="muted">Ej köpbar just nu</div>
              <?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Betyg</th>
          <?php foreach ($products as $product): ?>
            <?php $rating = (float) ($product['average_rating'] ?? 0); $reviews = (int) ($product['review_count'] ?? 0); ?>
            <td><?= number_format($rating, 1, ',', ' ') ?>/5 · <?= $reviews ?> recension<?= $reviews === 1 ? '' : 'er' ?></td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Kort beskrivning</th>
          <?php foreach ($products as $product): ?>
            <td>
              <?php $description = trim((string) ($product['description'] ?? '')); ?>
              <?= $description !== '' ? nl2br(htmlspecialchars(mb_strimwidth($description, 0, 180, '…'), ENT_QUOTES, 'UTF-8')) : '<span class="muted">Saknas</span>' ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <tr>
          <th>Åtgärd</th>
          <?php foreach ($products as $product): ?>
            <td>
              <form method="post" action="/compare/remove" class="inline-form">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="back_to" value="/compare">
                <button type="submit" class="btn-secondary">Ta bort</button>
              </form>
            </td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </section>
<?php endif; ?>

<?php
$content = (string) ob_get_clean();
$title = 'Jämför produkter | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
