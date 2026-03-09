<?php
$order = $detail['order'] ?? null;
$items = $detail['items'] ?? [];
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Order utskrift</title>
  <style>
    body{font:14px/1.4 Arial,sans-serif;color:#111;margin:24px;}
    h1,h2,h3{margin:0 0 8px;}
    .row{display:flex;gap:32px;margin-bottom:16px;}
    .col{flex:1;}
    table{width:100%;border-collapse:collapse;margin-top:8px;}
    th,td{border:1px solid #bbb;padding:6px;text-align:left;vertical-align:top;}
    .muted{color:#444;}
    @media print { .no-print{display:none;} body{margin:0;} }
  </style>
</head>
<body>
<?php if ($order === null): ?>
  <h1>Order saknas</h1>
<?php else: ?>
  <p class="no-print"><a href="javascript:window.print()">Skriv ut</a></p>
  <h1>Order <?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></h1>
  <p class="muted">Skapad: <?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
  <div class="row">
    <div class="col">
      <h3>Kund</h3>
      <p><?= htmlspecialchars((string) $order['customer_first_name'] . ' ' . (string) $order['customer_last_name'], ENT_QUOTES, 'UTF-8') ?><br>
      <?= htmlspecialchars((string) $order['customer_email'], ENT_QUOTES, 'UTF-8') ?><br>
      <?= htmlspecialchars((string) ($order['customer_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="col">
      <h3>Leveransadress</h3>
      <p><?= htmlspecialchars((string) $order['shipping_first_name'] . ' ' . (string) $order['shipping_last_name'], ENT_QUOTES, 'UTF-8') ?><br>
      <?= htmlspecialchars((string) $order['shipping_address_line_1'], ENT_QUOTES, 'UTF-8') ?><br>
      <?= htmlspecialchars((string) ($order['shipping_address_line_2'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
      <?= htmlspecialchars((string) $order['shipping_postal_code'] . ' ' . (string) $order['shipping_city'], ENT_QUOTES, 'UTF-8') ?><br>
      <?= htmlspecialchars((string) $order['shipping_country'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  </div>

  <h3>Plocklista</h3>
  <table>
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

  <p><strong>Total:</strong> <?= number_format((float) $order['total_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) $order['currency_code'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
</body>
</html>
