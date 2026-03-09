<?php
ob_start();
?>
<section class="panel">
  <h2>Orderstatus</h2>
  <form method="get" action="/order-status" class="inline-form">
    <div style="flex:1;">
      <label for="order_number">Ordernummer</label>
      <input id="order_number" type="text" name="order_number" value="<?= htmlspecialchars((string) ($queryOrderNumber ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="t.ex. AR-20260101-1234">
    </div>
    <button class="btn-primary" type="submit">Visa status</button>
  </form>

  <?php if (($orderSummary ?? null) !== null): ?>
    <p>Ordernummer: <strong><?= htmlspecialchars((string) $orderSummary['order_number'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Status: <strong><?= htmlspecialchars((string) $orderSummary['status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Leveransstatus: <strong><?= htmlspecialchars((string) $orderSummary['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Skickad: <?= htmlspecialchars((string) ($orderSummary['shipped_at'] ?? 'Inte skickad ännu'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Trackingnummer: <?= htmlspecialchars((string) ($orderSummary['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Fraktmetod: <?= htmlspecialchars((string) ($orderSummary['shipping_method'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php elseif (($showNotFound ?? false) === true): ?>
    <p class="err-msg">Ingen order hittades för det angivna ordernumret.</p>
  <?php else: ?>
    <p class="muted">Ange ordernummer för att se aktuell status.</p>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderstatus | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
