<?php
ob_start();
$items = $cartData['items'] ?? [];
$currency = (string) ($cartData['cart']['currency_code'] ?? 'SEK');
$shippingMethods = $shippingMethods ?? [];
$selectedShippingMethodCode = (string) ($selectedShippingMethodCode ?? '');
$totalsPreview = $totalsPreview ?? [
  'product_subtotal' => (float) ($cartData['subtotal_amount'] ?? 0),
  'shipping_cost' => 0,
  'discount_amount' => (float) ($cartData['discount_amount_inc_vat'] ?? 0),
  'grand_total' => (float) ($cartData['total_amount'] ?? 0),
];
$checkoutDefaults = $checkoutDefaults ?? [];
$inputValue = static function (string $key) use ($checkoutDefaults): string {
  return (string) ($checkoutDefaults[$key] ?? '');
};
?>
<section class="panel">
  <h2>Checkout</h2>
  <p class="muted">Fyll i uppgifter nedan för att skapa order. Vi återkopplar tydligt kring leverans och eventuella frågor.</p>
  <?php if (($error ?? '') !== ''): ?>
    <p class="err-msg"><strong>Kontrollera uppgifterna:</strong> <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if ($items === []): ?>
    <p class="muted">Kundvagnen är tom. <a href="/">Gå till startsidan</a>.</p>
  <?php else: ?>
    <div class="grid-2" style="align-items:start;">
      <div>
        <form method="post" action="/checkout/place-order">
          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Kunduppgifter</h3>
            <label>Förnamn *</label><input name="customer_first_name" required value="<?= htmlspecialchars($inputValue('customer_first_name'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Efternamn *</label><input name="customer_last_name" required value="<?= htmlspecialchars($inputValue('customer_last_name'), ENT_QUOTES, 'UTF-8') ?>">
            <label>E-post *</label><input name="customer_email" type="email" required value="<?= htmlspecialchars($inputValue('customer_email'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Telefon</label><input name="customer_phone" placeholder="För leveransfrågor" value="<?= htmlspecialchars($inputValue('customer_phone'), ENT_QUOTES, 'UTF-8') ?>">
          </section>

          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Fakturaadress</h3>
            <label>Adressrad 1 *</label><input name="billing_address_line_1" required value="<?= htmlspecialchars($inputValue('billing_address_line_1'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Adressrad 2</label><input name="billing_address_line_2" value="<?= htmlspecialchars($inputValue('billing_address_line_2'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Postnummer *</label><input name="billing_postal_code" required value="<?= htmlspecialchars($inputValue('billing_postal_code'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Stad *</label><input name="billing_city" required value="<?= htmlspecialchars($inputValue('billing_city'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Land (ISO2) *</label><input name="billing_country" value="<?= htmlspecialchars($inputValue('billing_country'), ENT_QUOTES, 'UTF-8') ?>" required>
          </section>

          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Leveransadress</h3>
            <label>Förnamn *</label><input name="shipping_first_name" required value="<?= htmlspecialchars($inputValue('shipping_first_name'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Efternamn *</label><input name="shipping_last_name" required value="<?= htmlspecialchars($inputValue('shipping_last_name'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Telefon</label><input name="shipping_phone" value="<?= htmlspecialchars($inputValue('shipping_phone'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Adressrad 1 *</label><input name="shipping_address_line_1" required value="<?= htmlspecialchars($inputValue('shipping_address_line_1'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Adressrad 2</label><input name="shipping_address_line_2" value="<?= htmlspecialchars($inputValue('shipping_address_line_2'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Postnummer *</label><input name="shipping_postal_code" required value="<?= htmlspecialchars($inputValue('shipping_postal_code'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Stad *</label><input name="shipping_city" required value="<?= htmlspecialchars($inputValue('shipping_city'), ENT_QUOTES, 'UTF-8') ?>">
            <label>Land (ISO2) *</label><input name="shipping_country" value="<?= htmlspecialchars($inputValue('shipping_country'), ENT_QUOTES, 'UTF-8') ?>" required>
            <label>Ordernotering</label><textarea name="order_notes" rows="4" placeholder="T.ex. företagsnamn, referens eller önskemål"></textarea>
          </section>

          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Fraktmetod</h3>
            <?php foreach ($shippingMethods as $method): ?>
              <label style="display:block; margin-bottom:.6rem; border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:.7rem;">
                <input type="radio" name="shipping_method_code" value="<?= htmlspecialchars((string) $method['code'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $method['code'] === $selectedShippingMethodCode) ? 'checked' : '' ?> required>
                <strong><?= htmlspecialchars((string) $method['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <small style="float:right;"><?= number_format((float) $method['price_inc_vat'], 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></small><br>
                <small class="muted"><?= htmlspecialchars((string) ($method['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
              </label>
            <?php endforeach; ?>
          </section>

          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Betalmetod</h3>
            <p class="muted">Välj hur du vill slutföra betalningen. Ingen extern onlinebetalning sker i detta steg.</p>
            <?php foreach (($paymentMethodOptions ?? []) as $method): ?>
              <label style="display:block; margin-bottom:.6rem; border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:.7rem;">
                <input type="radio" name="payment_method" value="<?= htmlspecialchars((string) $method['value'], ENT_QUOTES, 'UTF-8') ?>" <?= ($method['value'] === 'invoice_request') ? 'checked' : '' ?> required>
                <strong><?= htmlspecialchars((string) $method['label'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                <small class="muted"><?= htmlspecialchars((string) $method['help_text'], ENT_QUOTES, 'UTF-8') ?></small>
              </label>
            <?php endforeach; ?>
          </section>

          <button type="submit" class="btn-primary">Skapa order</button>
        </form>
      </div>

      <aside>
        <section class="panel" style="margin-bottom:.8rem;">
          <h3>Orderöversikt</h3>
          <table class="table">
            <thead><tr><th>Produkt</th><th>Antal</th><th>Radtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td><?= number_format((float) $item['line_total'], 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (($cartData['active_discount'] ?? null) !== null): ?>
            <p>Aktiv kampanj: <strong><?= htmlspecialchars((string) (($cartData['active_discount']['name'] ?? '') !== '' ? $cartData['active_discount']['name'] : $cartData['active_discount']['code']), ENT_QUOTES, 'UTF-8') ?></strong></p>
          <?php endif; ?>
          <p>Produktsubtotal: <?= number_format((float) $totalsPreview['product_subtotal'], 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></p>
          <p>Rabatt: -<?= number_format((float) ($totalsPreview['discount_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></p>
          <p>Frakt: <?= number_format((float) $totalsPreview['shipping_cost'], 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></p>
          <p><strong>Grand total: <?= number_format((float) $totalsPreview['grand_total'], 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></strong></p>
        </section>
      </aside>
    </div>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Checkout | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
