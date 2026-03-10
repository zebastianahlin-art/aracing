<?php
ob_start();
$filters = $filters ?? [];
$statusOptions = $statusOptions ?? [];
?>
<section class="card">
  <div class="topline"><h1>Orders</h1></div>

  <form method="get" action="/admin/orders" class="grid-4">
    <div>
      <label for="search">Sök (ordernr, kund, e-post, tracking)</label>
      <input id="search" type="text" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="order_status">Orderstatus</label>
      <select id="order_status" name="order_status">
        <option value="">Alla</option>
        <?php foreach (($statusOptions['order_status'] ?? []) as $status): ?>
          <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['order_status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="payment_status">Betalstatus</label>
      <select id="payment_status" name="payment_status">
        <option value="">Alla</option>
        <?php foreach (($statusOptions['payment_status'] ?? []) as $paymentStatus): ?>
          <option value="<?= htmlspecialchars((string) $paymentStatus, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['payment_status'] ?? '') === $paymentStatus ? 'selected' : '' ?>><?= htmlspecialchars((string) $paymentStatus, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="fulfillment_status">Fulfillment-status</label>
      <select id="fulfillment_status" name="fulfillment_status">
        <option value="">Alla</option>
        <?php foreach (($statusOptions['fulfillment_status'] ?? []) as $fulfillmentStatus): ?>
          <option value="<?= htmlspecialchars((string) $fulfillmentStatus, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['fulfillment_status'] ?? '') === $fulfillmentStatus ? 'selected' : '' ?>><?= htmlspecialchars((string) $fulfillmentStatus, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="actions-inline">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/orders">Rensa</a>
    </div>
  </form>

  <table class="table compact">
    <thead>
    <tr>
      <th>Ordernr</th><th>Kund</th><th>Fraktmetod</th><th>Total</th><th>Status</th><th>Leverans</th><th>Skapad</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
      <tr>
        <td><a href="/admin/orders/<?= (int) $order['id'] ?>"><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></a></td>
        <td>
          <?= htmlspecialchars(trim((string) $order['customer_first_name'] . ' ' . (string) $order['customer_last_name']), ENT_QUOTES, 'UTF-8') ?><br>
          <small><?= htmlspecialchars((string) $order['customer_email'], ENT_QUOTES, 'UTF-8') ?></small>
        </td>
        <td><?= htmlspecialchars((string) ($order['shipping_method_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br><small><?= number_format((float) ($order['shipping_cost_inc_vat'] ?? 0), 2, ',', ' ') ?></small></td>
        <td><?= number_format((float) $order['total_amount'], 2, ',', ' ') ?></td>
        <td>
          <small>Order: <span class="pill"><?= htmlspecialchars((string) $order['order_status'], ENT_QUOTES, 'UTF-8') ?></span></small><br>
          <small>Betalning: <span class="pill"><?= htmlspecialchars((string) $order['payment_status'], ENT_QUOTES, 'UTF-8') ?></span></small><br>
          <small>Fulfillment: <span class="pill"><?= htmlspecialchars((string) $order['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></span></small>
        </td>
        <td>
          <small>Carrier: <?= htmlspecialchars((string) ($order['carrier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small><br>
          <small>Tracking: <?= htmlspecialchars((string) ($order['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small><br>
          <small>Skickad: <?= htmlspecialchars((string) ($order['shipped_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
        </td>
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
