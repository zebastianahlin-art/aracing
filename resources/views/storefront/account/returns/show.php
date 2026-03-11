<?php
ob_start();
$request = $detail['request'] ?? null;
$items = $detail['items'] ?? [];
$statusLabels = $statusLabels ?? [];
$reasonLabels = $reasonLabels ?? [];
?>
<section class="panel">
  <?php if ($request === null): ?>
    <p class="err-msg">Returärendet kunde inte laddas.</p>
  <?php else: ?>
    <h2>Retur <?= htmlspecialchars((string) $request['return_number'], ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <p>Status: <strong><?= htmlspecialchars((string) ($statusLabels[$request['status']] ?? $request['status']), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Kopplad order: <a href="/account/orders/<?= (int) $request['order_id'] ?>"><?= htmlspecialchars((string) $request['order_number'], ENT_QUOTES, 'UTF-8') ?></a></p>
    <p>Skapad: <?= htmlspecialchars((string) $request['requested_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Anledning: <?= htmlspecialchars((string) (($reasonLabels[$request['reason_code']] ?? null) ?? ($request['reason_code'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Kundkommentar: <?= nl2br(htmlspecialchars((string) ($request['customer_comment'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></p>

    <table class="table">
      <thead><tr><th>Produkt</th><th>SKU</th><th>Antal</th><th>Orsak</th><th>Kommentar</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td><?= htmlspecialchars((string) (($reasonLabels[$item['reason_code']] ?? null) ?? ($item['reason_code'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['comment'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <p class="muted">Observera: Refund, returfrakt och lageråterföring hanteras inte automatiskt i denna version.</p>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Returdetalj | A-Racing';
require __DIR__ . '/../../../layouts/storefront.php';
