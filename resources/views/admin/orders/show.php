<?php
ob_start();
$order = $detail['order'] ?? null;
$items = $detail['items'] ?? [];
?>
<section class="card">
  <?php if ($order === null): ?>
    <h1>Order saknas</h1>
  <?php else: ?>
    <div class="topline"><h1>Order <?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></h1><a class="btn" href="/admin/orders">Tillbaka</a></div>
    <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <div class="grid">
      <div>
        <h3>Kund</h3>
        <p><?= htmlspecialchars((string) $order['customer_first_name'] . ' ' . (string) $order['customer_last_name'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['customer_email'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['customer_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>

        <h3>Fakturaadress</h3>
        <p><?= htmlspecialchars((string) $order['billing_address_line_1'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['billing_address_line_2'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['billing_postal_code'] . ' ' . (string) $order['billing_city'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['billing_country'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div>
        <h3>Leveransadress</h3>
        <p><?= htmlspecialchars((string) $order['shipping_first_name'] . ' ' . (string) $order['shipping_last_name'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['shipping_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['shipping_address_line_1'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['shipping_address_line_2'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['shipping_postal_code'] . ' ' . (string) $order['shipping_city'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['shipping_country'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>

    <h3>Orderrader</h3>
    <table class="table compact">
      <thead><tr><th>Produkt</th><th>SKU</th><th>Pris</th><th>Antal</th><th>Radtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= number_format((float) $item['unit_price_snapshot'], 2, ',', ' ') ?></td>
          <td><?= (int) $item['quantity'] ?></td>
          <td><?= number_format((float) $item['line_total'], 2, ',', ' ') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h3>Summering</h3>
    <p>Subtotal: <?= number_format((float) $order['subtotal_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) $order['currency_code'], ENT_QUOTES, 'UTF-8') ?><br>
      Frakt: <?= number_format((float) $order['shipping_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) $order['currency_code'], ENT_QUOTES, 'UTF-8') ?><br>
      <strong>Total: <?= number_format((float) $order['total_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) $order['currency_code'], ENT_QUOTES, 'UTF-8') ?></strong></p>

    <h3>Status</h3>
    <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/status" class="grid-3">
      <div>
        <label>Orderstatus</label>
        <select name="status">
          <?php foreach (($statusOptions['status'] ?? []) as $status): ?>
            <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Betalstatus</label>
        <select name="payment_status">
          <?php foreach (($statusOptions['payment_status'] ?? []) as $status): ?>
            <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= $order['payment_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Leveransstatus</label>
        <select name="fulfillment_status">
          <?php foreach (($statusOptions['fulfillment_status'] ?? []) as $status): ?>
            <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= $order['fulfillment_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><button class="btn" type="submit">Uppdatera</button></div>
    </form>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderdetalj | Admin';
require __DIR__ . '/../../layouts/admin.php';
