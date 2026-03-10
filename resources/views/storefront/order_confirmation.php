<?php
ob_start();
?>
<section class="panel">
  <h2>Tack för din order</h2>
  <?php if ($orderNumber === null): ?>
    <p class="muted">Ingen ny order hittades i sessionen.</p>
  <?php else: ?>
    <p>Ordernummer: <strong><?= htmlspecialchars((string) $orderNumber, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <?php if (($publicOrder ?? null) !== null): ?>
      <p>Orderstatus: <strong><?= htmlspecialchars((string) $publicOrder['order_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Fulfillment-status: <strong><?= htmlspecialchars((string) $publicOrder['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Betalstatus: <strong><?= htmlspecialchars((string) ($publicOrder['payment_status'] ?? 'unpaid'), ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Betalmetod: <strong><?= htmlspecialchars((string) ($paymentMethodLabel ?? 'Ej vald'), ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Fraktmetod: <strong><?= htmlspecialchars((string) ($publicOrder['shipping_method_name'] ?? 'Ej vald'), ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Produktsubtotal: <?= number_format((float) ($publicOrder['product_subtotal'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($publicOrder['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Fraktkostnad: <?= number_format((float) ($publicOrder['shipping_cost_inc_vat'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($publicOrder['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php if ((float) ($publicOrder['discount_amount_inc_vat'] ?? 0) > 0): ?>
        <p>Rabatt<?= (($publicOrder['discount_code'] ?? '') !== '') ? ' (' . htmlspecialchars((string) $publicOrder['discount_code'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>: -<?= number_format((float) ($publicOrder['discount_amount_inc_vat'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($publicOrder['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <p><strong>Grand total: <?= number_format((float) ($publicOrder['grand_total'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($publicOrder['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p class="muted">Nästa steg: <?= htmlspecialchars((string) ($paymentNextStepText ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Skickad: <?= htmlspecialchars((string) ($publicOrder['shipped_at'] ?? 'Inte skickad ännu'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Trackingnummer: <?= htmlspecialchars((string) ($publicOrder['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Carrier: <?= htmlspecialchars((string) ($publicOrder['carrier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Trackinglänk: <?= htmlspecialchars((string) ($publicOrder['tracking_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Levererad: <?= htmlspecialchars((string) ($publicOrder['delivered_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p><a class="btn-secondary" href="/order-status?order_number=<?= urlencode((string) $orderNumber) ?>">Visa orderstatus</a></p>
  <?php endif; ?>
  <p><a class="btn-primary" href="/">Till startsidan</a></p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderbekräftelse | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
