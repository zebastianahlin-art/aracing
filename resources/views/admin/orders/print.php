<?php
$order = $detail['order'] ?? null;
$document = $detail['fulfillment_document'] ?? [];
$lines = $document['lines'] ?? [];
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Plock-/packunderlag</title>
  <style>
    body{font:14px/1.4 Arial,sans-serif;color:#111;margin:24px;}
    h1,h2,h3{margin:0 0 8px;}
    table{width:100%;border-collapse:collapse;margin-top:8px;}
    th,td{border:1px solid #bbb;padding:6px;text-align:left;vertical-align:top;}
    .muted{color:#444;} @media print { .no-print{display:none;} body{margin:0;} }
  </style>
</head>
<body>
<?php if ($order === null): ?>
  <h1>Order saknas</h1>
<?php else: ?>
  <p class="no-print"><a href="javascript:window.print()">Skriv ut</a></p>
  <h1>Plock-/packunderlag: <?= htmlspecialchars((string) ($document['order_number'] ?? $order['order_number']), ENT_QUOTES, 'UTF-8') ?></h1>
  <p class="muted">
    Skapad: <?= htmlspecialchars((string) ($document['created_at'] ?? $order['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> |
    Fraktmetod: <?= htmlspecialchars((string) ($document['shipping_method'] ?? $order['shipping_method_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
  </p>

  <p><strong>Plocknotering:</strong> <?= nl2br(htmlspecialchars((string) ($document['pick_note'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></p>
  <p><strong>Packnotering:</strong> <?= nl2br(htmlspecialchars((string) ($document['pack_note'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></p>

  <table>
    <thead><tr><th>Produkt</th><th>SKU</th><th>Antal</th><th>Lagersignal</th><th>Check</th></tr></thead>
    <tbody>
    <?php foreach ($lines as $line): ?>
      <tr>
        <td><?= htmlspecialchars((string) ($line['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($line['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int) ($line['quantity'] ?? 0) ?></td>
        <td><?= htmlspecialchars((string) ($line['stock_status'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?></td>
        <td>☐</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <p><strong>Summering:</strong> <?= (int) ($document['line_count'] ?? 0) ?> rader / <?= (int) ($document['total_quantity'] ?? 0) ?> artiklar.</p>
<?php endif; ?>
</body>
</html>
