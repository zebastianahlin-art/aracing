<?php
ob_start();
$request = $detail['request'] ?? null;
$order = $detail['order'] ?? null;
$items = $detail['items'] ?? [];
$history = $detail['history'] ?? [];
$allowedStatuses = $detail['allowed_statuses'] ?? [];
$statusLabels = $statusLabels ?? [];
$reasonLabels = $reasonLabels ?? [];
?>
<section class="card">
  <?php if ($request === null): ?>
    <h1>Returärende saknas</h1>
  <?php else: ?>
    <div class="topline"><h1>Retur <?= htmlspecialchars((string) $request['return_number'], ENT_QUOTES, 'UTF-8') ?></h1><a class="btn" href="/admin/returns">Tillbaka</a></div>
    <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <p>Status: <strong><?= htmlspecialchars((string) ($statusLabels[$request['status']] ?? $request['status']), ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p>Order: <a href="/admin/orders/<?= (int) $request['order_id'] ?>"><?= htmlspecialchars((string) $request['order_number'], ENT_QUOTES, 'UTF-8') ?></a></p>
    <p>Kund: <?= htmlspecialchars((string) ($request['customer_first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($request['customer_last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($request['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</p>
    <p>Skapad: <?= htmlspecialchars((string) $request['requested_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <p>Kundorsak: <?= htmlspecialchars((string) (($reasonLabels[$request['reason_code']] ?? null) ?? ($request['reason_code'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></p>
    <p>Kundkommentar: <?= nl2br(htmlspecialchars((string) ($request['customer_comment'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></p>
    <p>Adminnotering: <?= nl2br(htmlspecialchars((string) ($request['admin_note'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></p>

    <div class="actions-inline">
      <?php foreach ($allowedStatuses as $status): ?>
        <form method="post" action="/admin/returns/<?= (int) $request['id'] ?>/status">
          <input type="hidden" name="status" value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>">
          <button class="btn" type="submit">Sätt <?= htmlspecialchars((string) ($statusLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?></button>
        </form>
      <?php endforeach; ?>
    </div>

    <div class="grid">
      <div>
        <h3>Returnerade rader</h3>
        <table class="table compact">
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

        <h3>Ny adminnotering</h3>
        <form method="post" action="/admin/returns/<?= (int) $request['id'] ?>/notes">
          <textarea id="admin_note" name="admin_note" required></textarea>
          <button class="btn" type="submit">Spara notering</button>
        </form>
      </div>
      <div>
        <h3>Historik</h3>
        <?php foreach ($history as $event): ?>
          <article class="timeline-item">
            <strong><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></strong>
            <div><?= htmlspecialchars((string) ($event['from_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) ($event['to_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            <div><?= nl2br(htmlspecialchars((string) ($event['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
            <small><?= htmlspecialchars((string) $event['created_at'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($event['created_by_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </article>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($order !== null): ?>
      <p><small>Orderstatus: <?= htmlspecialchars((string) ($order['order_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> / Fulfillment: <?= htmlspecialchars((string) ($order['fulfillment_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small></p>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Returdetalj | Admin';
require __DIR__ . '/../../layouts/admin.php';
