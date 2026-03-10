<?php
ob_start();
$order = $detail['order'] ?? null;
$items = $detail['items'] ?? [];
$history = $detail['history'] ?? [];
$emails = $detail['emails'] ?? [];

$canConfirm = ($order['order_status'] ?? '') === 'placed';
$canProcessing = ($order['order_status'] ?? '') === 'confirmed';
$canComplete = ($order['order_status'] ?? '') === 'processing';
$canCancelOrder = in_array((string) ($order['order_status'] ?? ''), ['placed', 'confirmed', 'processing'], true);
$canPicking = ($order['fulfillment_status'] ?? '') === 'unfulfilled';
$canPacked = ($order['fulfillment_status'] ?? '') === 'picking';
$canShipped = ($order['fulfillment_status'] ?? '') === 'packed';
$canDelivered = ($order['fulfillment_status'] ?? '') === 'shipped';
$canCancelFulfillment = in_array((string) ($order['fulfillment_status'] ?? ''), ['unfulfilled', 'picking', 'packed'], true);
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
      <?php if ($canConfirm): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/order-status"><input type="hidden" name="order_status" value="confirmed"><button class="btn" type="submit">Bekräfta order</button></form><?php endif; ?>
      <?php if ($canProcessing): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/order-status"><input type="hidden" name="order_status" value="processing"><button class="btn" type="submit">Markera under behandling</button></form><?php endif; ?>
      <?php if ($canComplete): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/order-status"><input type="hidden" name="order_status" value="completed"><button class="btn" type="submit">Markera slutförd</button></form><?php endif; ?>
      <?php if ($canPicking): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfillment-status"><input type="hidden" name="fulfillment_status" value="picking"><button class="btn" type="submit">Markera plockas</button></form><?php endif; ?>
      <?php if ($canPacked): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfillment-status"><input type="hidden" name="fulfillment_status" value="packed"><button class="btn" type="submit">Markera packad</button></form><?php endif; ?>
      <?php if ($canCancelOrder): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/order-status"><input type="hidden" name="order_status" value="cancelled"><button class="btn" type="submit">Annullera order</button></form><?php endif; ?>
      <?php if ($canCancelFulfillment): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfillment-status"><input type="hidden" name="fulfillment_status" value="cancelled"><button class="btn" type="submit">Annullera fulfillment</button></form><?php endif; ?>
      <?php if ($canDelivered): ?><form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfillment-status"><input type="hidden" name="fulfillment_status" value="delivered"><button class="btn" type="submit">Markera levererad</button></form><?php endif; ?>
    </div>

    <h3>Betalhändelser</h3>
    <table class="table compact">
      <thead><tr><th>Tid</th><th>Typ</th><th>Provider-event</th><th>Ref</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach (($paymentEvents ?? []) as $event): ?>
        <tr>
          <td><?= htmlspecialchars((string) ($event['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($event['event_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($event['provider_event_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($event['payment_reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($event['status_before'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) ($event['status_after'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

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
        <h3>Statusöversikt</h3>
        <p>
          Orderstatus: <span class="pill"><?= htmlspecialchars((string) $order['order_status'], ENT_QUOTES, 'UTF-8') ?></span><br>
          Betalstatus: <span class="pill"><?= htmlspecialchars((string) $order['payment_status'], ENT_QUOTES, 'UTF-8') ?></span><br>
          Betalmetod: <?= htmlspecialchars((string) ($order['payment_method'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Betalprovider: <?= htmlspecialchars((string) ($order['payment_provider'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Provider status: <?= htmlspecialchars((string) ($order['payment_provider_status'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Provider session: <?= htmlspecialchars((string) ($order['payment_provider_session_id'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Provider reference: <?= htmlspecialchars((string) ($order['payment_provider_reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Authorized: <?= htmlspecialchars((string) ($order['payment_authorized_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Paid: <?= htmlspecialchars((string) ($order['payment_paid_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Failed: <?= htmlspecialchars((string) ($order['payment_failed_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Fulfillment: <span class="pill"><?= htmlspecialchars((string) $order['fulfillment_status'], ENT_QUOTES, 'UTF-8') ?></span>
        </p>

        <h3>Rabatt (snapshot)</h3>
        <p>
          Kod: <?= htmlspecialchars((string) ($order['discount_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Namn: <?= htmlspecialchars((string) ($order['discount_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Typ/värde: <?= htmlspecialchars((string) ($order['discount_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> / <?= number_format((float) ($order['discount_value'] ?? 0), 2, ',', ' ') ?><br>
          Faktisk rabatt: -<?= number_format((float) ($order['discount_amount_inc_vat'] ?? 0), 2, ',', ' ') ?>
        </p>

        <h3>Fraktmetod (snapshot)</h3>
        <p>
          Metod: <?= htmlspecialchars((string) ($order['shipping_method_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($order['shipping_method_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)<br>
          Beskrivning: <?= nl2br(htmlspecialchars((string) ($order['shipping_method_description'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?><br>
          Frakt ex moms: <?= number_format((float) ($order['shipping_cost_ex_vat'] ?? 0), 2, ',', ' ') ?><br>
          Frakt inkl moms: <?= number_format((float) ($order['shipping_cost_inc_vat'] ?? 0), 2, ',', ' ') ?><br>
          Produktsubtotal: <?= number_format((float) ($order['subtotal_amount'] ?? 0), 2, ',', ' ') ?><br>
          Grand total: <strong><?= number_format((float) ($order['total_amount'] ?? 0), 2, ',', ' ') ?></strong>
        </p>

        <h3>Leveransinfo</h3>
        <p>
          Carrier: <?= htmlspecialchars((string) ($order['carrier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($order['carrier_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>)<br>
          Trackingnummer: <?= htmlspecialchars((string) ($order['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Tracking-URL: <?= htmlspecialchars((string) ($order['tracking_url'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Shipped at: <?= htmlspecialchars((string) ($order['shipped_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Delivered at: <?= htmlspecialchars((string) ($order['delivered_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
          Cancelled at: <?= htmlspecialchars((string) ($order['cancelled_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
        </p>
      </div>
    </div>

    <h3>Uppdatera tracking/fraktinfo</h3>
    <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/shipment" class="grid-4">
      <div><label>carrier_code</label><input type="text" name="carrier_code" value="<?= htmlspecialchars((string) ($order['carrier_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>carrier_name</label><input type="text" name="carrier_name" value="<?= htmlspecialchars((string) ($order['carrier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>tracking_number</label><input type="text" name="tracking_number" value="<?= htmlspecialchars((string) ($order['tracking_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>tracking_url</label><input type="text" name="tracking_url" value="<?= htmlspecialchars((string) ($order['tracking_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>shipped_at</label><input type="text" name="shipped_at" value="<?= htmlspecialchars((string) ($order['shipped_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="YYYY-MM-DD HH:MM:SS"></div>
      <div><button class="btn" type="submit">Spara fraktinfo</button></div>
    </form>

    <?php if ($canShipped): ?>
      <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/fulfillment-status">
        <input type="hidden" name="fulfillment_status" value="shipped">
        <button class="btn" type="submit">Markera skickad</button>
      </form>
    <?php endif; ?>

    <h3>Uppdatera betalning</h3>
    <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/payment" class="grid-4">
      <div>
        <label>Betalstatus</label>
        <select name="payment_status">
          <?php foreach (($statusOptions['payment_status'] ?? []) as $status): ?>
            <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= $order['payment_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Betalreferens</label><input type="text" name="payment_reference" value="<?= htmlspecialchars((string) ($order['payment_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Betalningsnotering</label><textarea name="payment_note"><?= htmlspecialchars((string) ($order['payment_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></div>
      <div><button class="btn" type="submit">Spara betalningsinfo</button></div>
    </form>

    <h3>Intern referens</h3>
    <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/internal-reference">
      <input type="text" name="internal_reference" value="<?= htmlspecialchars((string) ($order['internal_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <button class="btn" type="submit">Spara intern referens</button>
    </form>

    <h3>Plocklista</h3>
    <table class="table compact">
      <thead><tr><th>Produktnamn</th><th>SKU</th><th>Antal</th><th>Pris</th><th>Radtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><strong><?= (int) $item['quantity'] ?></strong></td>
          <td><?= number_format((float) $item['unit_price_snapshot'], 2, ',', ' ') ?></td>
          <td><?= number_format((float) $item['line_total'], 2, ',', ' ') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <h3>E-posthistorik</h3>
    <table class="table compact">
      <thead><tr><th>Typ</th><th>Mottagare</th><th>Ämne</th><th>Status</th><th>Skickad</th><th>Fel</th></tr></thead>
      <tbody>
      <?php if ($emails === []): ?>
        <tr><td colspan="6">Ingen e-post loggad för ordern ännu.</td></tr>
      <?php else: ?>
        <?php foreach ($emails as $email): ?>
          <tr>
            <td><?= htmlspecialchars((string) $email['email_type'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $email['recipient_email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $email['subject'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="pill"><?= htmlspecialchars((string) $email['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= htmlspecialchars((string) ($email['sent_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= nl2br(htmlspecialchars((string) ($email['error_message'] ?? '-'), ENT_QUOTES, 'UTF-8')) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>

    <div class="grid">
      <div>
        <h3>Ny adminnotering</h3>
        <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/notes">
          <textarea id="note_text" name="note_text" required></textarea>
          <button class="btn" type="submit">Lägg till</button>
        </form>
      </div>
      <div>
        <h3>Timeline</h3>
        <?php foreach ($history as $event): ?>
          <article class="timeline-item">
            <strong><?= htmlspecialchars((string) $event['type'], ENT_QUOTES, 'UTF-8') ?></strong>
            <div><?= htmlspecialchars((string) ($event['from_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) ($event['to_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            <div><?= nl2br(htmlspecialchars((string) ($event['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
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
