<?php
ob_start();
$items = $cartData['items'] ?? [];
?>
<section class="panel">
  <h2>Kundvagn</h2>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <?php if ($items === []): ?>
    <p class="muted">Din kundvagn är tom.</p>
  <?php else: ?>
    <table class="table">
      <thead>
      <tr>
        <th>Produkt</th><th>Pris</th><th>Antal</th><th>Radtotal</th><th></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?><br>
            <span class="muted">SKU: <?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
          </td>
          <td><?= number_format((float) $item['unit_price_snapshot'], 2, ',', ' ') ?> <?= htmlspecialchars((string) $cartData['cart']['currency_code'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <form method="post" action="/cart/items/update" class="inline-form">
              <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
              <input type="number" name="quantity" min="0" value="<?= (int) $item['quantity'] ?>" style="max-width:70px;">
              <button class="btn-secondary" type="submit">Spara</button>
            </form>
          </td>
          <td><?= number_format((float) $item['line_total'], 2, ',', ' ') ?> <?= htmlspecialchars((string) $cartData['cart']['currency_code'], ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <form method="post" action="/cart/items/remove">
              <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
              <button class="btn-danger" type="submit">Ta bort</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p><strong>Totalsumma: <?= number_format((float) ($cartData['total_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) $cartData['cart']['currency_code'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p><a class="btn-primary" href="/checkout">Till checkout</a></p>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kundvagn | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
