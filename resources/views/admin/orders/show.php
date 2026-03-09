<?php
ob_start();
$order = $detail['order'] ?? null;
$items = $detail['items'] ?? [];
$notes = $detail['notes'] ?? [];
$events = $detail['events'] ?? [];
?>
<section class="card">
  <?php if ($order === null): ?>
    <h1>Order saknas</h1>
  <?php else: ?>
    <div class="topline"><h1>Order <?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></h1><a class="btn" href="/admin/orders">Tillbaka</a></div>
    <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <div class="actions-inline">
      <a class="btn" href="/admin/orders/<?= (int) $order['id'] ?>/print" target="_blank" rel="noopener">Utskriftsvy</a>
      <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/mark-processing"><button class="btn" type="submit">Markera processing</button></form>
      <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/mark-packed"><button class="btn" type="submit">Markera packad</button></form>
      <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/mark-shipped"><button class="btn" type="submit">Markera skickad</button></form>
    </div>

    <div class="grid">
      <div>
        <h3>Kund</h3>
        <p><?= htmlspecialchars((string) $order['customer_first_name'] . ' ' . (string) $order['customer_last_name'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['customer_email'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['customer_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>

        <h3>Leveransadress</h3>
        <p><?= htmlspecialchars((string) $order['shipping_first_name'] . ' ' . (string) $order['shipping_last_name'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['shipping_phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['shipping_address_line_1'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) ($order['shipping_address_line_2'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['shipping_postal_code'] . ' ' . (string) $order['shipping_city'], ENT_QUOTES, 'UTF-8') ?><br>
        <?= htmlspecialchars((string) $order['shipping_country'], ENT_QUOTES, 'UTF-8') ?></p>

        <h3>Kundanteckning</h3>
        <p><?= nl2br(htmlspecialchars((string) ($order['order_notes'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></p>
      </div>
      <div>
        <h3>Operativ status</h3>
        <p>
          <span class="pill"><?= htmlspecialchars((string) $order['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></span><br>
          Intern referens: <?= htmlspecialchars((string) ($order['internal_reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Packad: <?= htmlspecialchars((string) ($order['packed_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Skickad: <?= htmlspecialchars((string) ($order['shipped_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <h3>Manuell försändelseinfo</h3>
        <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/shipment">
          <label for="tracking_number">Trackingnummer</label>
          <input id="tracking_number" type="text" name="tracking_number" value="<?= htmlspecialchars((string) ($order['tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

          <label for="shipping_method">Fraktmetod</label>
          <input id="shipping_method" type="text" name="shipping_method" value="<?= htmlspecialchars((string) ($order['shipping_method'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

          <label for="shipped_by_name">Skickad av</label>
          <input id="shipped_by_name" type="text" name="shipped_by_name" value="<?= htmlspecialchars((string) ($order['shipped_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

          <label for="shipment_note">Försändelsenotering</label>
          <textarea id="shipment_note" name="shipment_note"><?= htmlspecialchars((string) ($order['shipment_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

          <button class="btn" type="submit">Spara försändelseinfo</button>
        </form>
      </div>
    </div>

    <h3>Plocklista</h3>
    <table class="table compact">
      <thead><tr><th>Produktnamn</th><th>SKU</th><th>Antal</th><th>Intern ref</th><th>Pris</th><th>Radtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><strong><?= (int) $item['quantity'] ?></strong></td>
          <td><?= htmlspecialchars((string) ($order['internal_reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= number_format((float) $item['unit_price_snapshot'], 2, ',', ' ') ?></td>
          <td><?= number_format((float) $item['line_total'], 2, ',', ' ') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h3>Uppdatera order</h3>
    <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/update" class="grid-4">
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
      <div>
        <label>Intern referens</label>
        <input type="text" name="internal_reference" value="<?= htmlspecialchars((string) ($order['internal_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div><button class="btn" type="submit">Spara uppdatering</button></div>
    </form>

    <div class="grid">
      <div>
        <h3>Interna anteckningar</h3>
        <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/notes">
          <label for="note_text">Ny anteckning</label>
          <textarea id="note_text" name="note_text" required></textarea>
          <button class="btn" type="submit">Lägg till anteckning</button>
        </form>

        <?php foreach ($notes as $note): ?>
          <article class="timeline-item">
            <strong><?= htmlspecialchars((string) $note['note_type'], ENT_QUOTES, 'UTF-8') ?></strong>
            <div><?= nl2br(htmlspecialchars((string) $note['note_text'], ENT_QUOTES, 'UTF-8')) ?></div>
            <small><?= htmlspecialchars((string) $note['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
          </article>
        <?php endforeach; ?>
      </div>

      <div>
        <h3>Historik</h3>
        <?php foreach ($events as $event): ?>
          <article class="timeline-item">
            <strong><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></strong>
            <div><?= htmlspecialchars((string) $event['event_message'], ENT_QUOTES, 'UTF-8') ?></div>
            <small><?= htmlspecialchars((string) $event['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderdetalj | Admin';
require __DIR__ . '/../../layouts/admin.php';
