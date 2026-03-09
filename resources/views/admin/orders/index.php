<?php
ob_start();
?>
<section class="card">
  <div class="topline"><h1>Orders</h1></div>
  <table class="table compact">
    <thead>
    <tr>
      <th>Ordernr</th><th>Kund</th><th>E-post</th><th>Status</th><th>Betalning</th><th>Leverans</th><th>Total</th><th>Skapad</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
      <tr>
        <td><a href="/admin/orders/<?= (int) $order['id'] ?>"><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></a></td>
        <td><?= htmlspecialchars(trim((string) $order['customer_first_name'] . ' ' . (string) $order['customer_last_name']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $order['customer_email'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="pill"><?= htmlspecialchars((string) $order['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><span class="pill"><?= htmlspecialchars((string) $order['payment_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><span class="pill"><?= htmlspecialchars((string) $order['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= number_format((float) $order['total_amount'], 2, ',', ' ') ?></td>
        <td><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orders | Admin';
require __DIR__ . '/../../layouts/admin.php';
