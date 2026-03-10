<?php
/** @var array<string, mixed> $order */
/** @var array<int, array<string, mixed>> $items */
?>
<h2>Tack för din order hos A-Racing</h2>
<p>Hej <?= htmlspecialchars(trim((string) ($order['customer_first_name'] ?? '') . ' ' . (string) ($order['customer_last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>,</p>
<p>Vi har tagit emot din order <strong><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></strong>.</p>

<h3>Ordersammanfattning</h3>
<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%;max-width:760px;">
  <thead>
    <tr><th align="left">Produkt</th><th align="left">SKU</th><th align="right">Antal</th><th align="right">Pris</th><th align="right">Radtotal</th></tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td align="right"><?= (int) $item['quantity'] ?></td>
        <td align="right"><?= number_format((float) $item['unit_price_snapshot'], 2, ',', ' ') ?></td>
        <td align="right"><?= number_format((float) $item['line_total'], 2, ',', ' ') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<p>
  Delsumma: <strong><?= number_format((float) ($order['subtotal_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong><br>
  Frakt: <strong><?= number_format((float) ($order['shipping_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong><br>
  Totalt: <strong><?= number_format((float) ($order['total_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></strong><br>
  Betalmetod: <strong><?= htmlspecialchars((string) ($paymentMethodLabel ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
  Betalstatus: <strong><?= htmlspecialchars((string) ($order['payment_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
</p>

<p><?= htmlspecialchars((string) ($paymentNextStepText ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
<p>Du kan följa orderstatus här: <a href="<?= htmlspecialchars((string) ($orderStatusUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($orderStatusUrl ?? ''), ENT_QUOTES, 'UTF-8') ?></a></p>
