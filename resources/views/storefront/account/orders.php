<?php
ob_start();
$orders = $orders ?? [];
$error = trim((string) ($_GET['error'] ?? ''));
?>
<section class="panel">
  <h2>Orderhistorik</h2>
  <?php if ($error !== ''): ?><p class="err-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($orders === []): ?>
    <p class="muted">Du har inga ordrar ännu.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Ordernummer</th><th>Datum</th><th>Orderstatus</th><th>Betalstatus</th><th>Leveransstatus</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $order['order_status'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $order['payment_status'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $order['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= number_format((float) $order['total_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/account/orders/<?= (int) $order['id'] ?>">Visa</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderhistorik | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
