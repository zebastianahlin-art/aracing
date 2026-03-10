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
    <p>Orderstatus: <strong><?= htmlspecialchars((string) $orderSummary['order_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Betalstatus: <strong><?= htmlspecialchars((string) ($orderSummary['payment_status'] ?? 'unpaid'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Betalmetod: <strong><?= htmlspecialchars((string) ($paymentMethodLabel ?? 'Ej vald'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Leveransstatus: <strong><?= htmlspecialchars((string) $orderSummary['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Fraktmetod: <strong><?= htmlspecialchars((string) ($orderSummary['shipping_method_name'] ?? 'Ej vald'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Fraktkostnad: <?= number_format((float) ($orderSummary['shipping_cost_inc_vat'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($orderSummary['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Grand total: <?= number_format((float) ($orderSummary['grand_total'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($orderSummary['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Skickad: <?= htmlspecialchars((string) ($orderSummary['shipped_at'] ?? 'Inte skickad ännu'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Trackingnummer: <?= htmlspecialchars((string) ($orderSummary['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Carrier: <?= htmlspecialchars((string) ($orderSummary['carrier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Trackinglänk: <?= htmlspecialchars((string) ($orderSummary['tracking_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Levererad: <?= htmlspecialchars((string) ($orderSummary['delivered_at'] ?? 'Inte levererad ännu'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Betalreferens: <?= htmlspecialchars((string) ($orderSummary['payment_reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Betalningsinfo: <?= nl2br(htmlspecialchars((string) ($orderSummary['payment_note'] ?? ($paymentNextStepText ?? '-')), ENT_QUOTES, 'UTF-8')) ?></p>
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
