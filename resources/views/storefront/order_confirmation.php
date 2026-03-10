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
      <p>Status: <strong><?= htmlspecialchars((string) $publicOrder['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Betalstatus: <strong><?= htmlspecialchars((string) ($publicOrder['payment_status'] ?? 'unpaid'), ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p>Betalmetod: <strong><?= htmlspecialchars((string) ($paymentMethodLabel ?? 'Ej vald'), ENT_QUOTES, 'UTF-8') ?></strong></p>
      <p class="muted">Nästa steg: <?= htmlspecialchars((string) ($paymentNextStepText ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Skickad: <?= htmlspecialchars((string) ($publicOrder['shipped_at'] ?? 'Inte skickad ännu'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Trackingnummer: <?= htmlspecialchars((string) ($publicOrder['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      <p>Fraktmetod: <?= htmlspecialchars((string) ($publicOrder['shipping_method'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p><a class="btn-secondary" href="/order-status?order_number=<?= urlencode((string) $orderNumber) ?>">Visa orderstatus</a></p>
  <?php endif; ?>
  <p><a class="btn-primary" href="/">Till startsidan</a></p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderbekräftelse | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
