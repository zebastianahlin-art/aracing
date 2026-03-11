<?php
ob_start();
$order = $detail['order'] ?? null;
$items = $detail['items'] ?? [];
?>
<section class="panel">
  <?php if ($order === null): ?>
    <p class="err-msg">Ordern kunde inte laddas.</p>
  <?php else: ?>
    <h2>Order <?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Datum: <?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Orderstatus: <strong><?= htmlspecialchars((string) $order['order_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Betalstatus: <strong><?= htmlspecialchars((string) $order['payment_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Leveransstatus: <strong><?= htmlspecialchars((string) $order['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Frakt: <?= htmlspecialchars((string) ($order['shipping_method_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float) ($order['shipping_cost_inc_vat'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?>)</p>
    <p>Rabatt: -<?= number_format((float) ($order['discount_amount_inc_vat'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Total: <?= number_format((float) $order['total_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Tracking: <?= htmlspecialchars((string) ($order['tracking_number'] ?? 'Saknas ännu'), ENT_QUOTES, 'UTF-8') ?></p>

    <table class="table">
      <thead><tr><th>Produkt</th><th>SKU</th><th>Antal</th><th>Pris</th><th>Radtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td><?= number_format((float) $item['unit_price_snapshot'], 2, ',', ' ') ?></td>
          <td><?= number_format((float) $item['line_total'], 2, ',', ' ') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderdetalj | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
