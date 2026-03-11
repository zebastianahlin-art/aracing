<?php
$draft = $detail['draft'] ?? null;
$items = $detail['items'] ?? [];
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <title>Supplier order <?= htmlspecialchars((string) ($draft['order_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
    h1, h2 { margin: 0 0 10px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 14px; }
    th, td { border: 1px solid #bbb; padding: 8px; text-align: left; }
    .meta p { margin: 2px 0; }
    @media print { .no-print { display:none; } }
  </style>
</head>
<body>
  <?php if ($draft === null): ?>
    <p>Utkast hittades inte.</p>
  <?php else: ?>
    <div class="no-print" style="margin-bottom:12px;"><button onclick="window.print()">Skriv ut</button></div>
    <h1>Supplier order underlag</h1>
    <div class="meta">
      <p><strong>Ordernummer:</strong> <?= htmlspecialchars((string) $draft['order_number'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Leverantör:</strong> <?= htmlspecialchars((string) ($draft['supplier_name_snapshot'] ?? 'Saknas'), ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Status:</strong> <?= htmlspecialchars((string) $draft['status'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><strong>Skapad:</strong> <?= htmlspecialchars((string) $draft['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php if (!empty($draft['internal_note'])): ?>
        <p><strong>Intern notering:</strong> <?= nl2br(htmlspecialchars((string) $draft['internal_note'], ENT_QUOTES, 'UTF-8')) ?></p>
      <?php endif; ?>
    </div>

    <table>
      <thead>
        <tr><th>Produkt</th><th>Intern SKU</th><th>Lev. SKU</th><th>Kvantitet</th><th>Kostnad snapshot</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['supplier_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td><?= htmlspecialchars((string) ($item['unit_cost_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
