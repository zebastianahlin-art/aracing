<?php
ob_start();
$items = $cartData['items'] ?? [];
$currency = (string) ($cartData['cart']['currency_code'] ?? 'SEK');
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
            <label>Förnamn *</label><input name="customer_first_name" required>
            <label>Efternamn *</label><input name="customer_last_name" required>
            <label>E-post *</label><input name="customer_email" type="email" required>
            <label>Telefon</label><input name="customer_phone" placeholder="För leveransfrågor">
          </section>

          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Fakturaadress</h3>
            <label>Adressrad 1 *</label><input name="billing_address_line_1" required>
            <label>Adressrad 2</label><input name="billing_address_line_2">
            <label>Postnummer *</label><input name="billing_postal_code" required>
            <label>Stad *</label><input name="billing_city" required>
            <label>Land (ISO2) *</label><input name="billing_country" value="SE" required>
          </section>

          <section class="panel" style="margin-bottom:.8rem;">
            <h3>Leveransadress</h3>
            <label>Förnamn *</label><input name="shipping_first_name" required>
            <label>Efternamn *</label><input name="shipping_last_name" required>
            <label>Telefon</label><input name="shipping_phone">
            <label>Adressrad 1 *</label><input name="shipping_address_line_1" required>
            <label>Adressrad 2</label><input name="shipping_address_line_2">
            <label>Postnummer *</label><input name="shipping_postal_code" required>
            <label>Stad *</label><input name="shipping_city" required>
            <label>Land (ISO2) *</label><input name="shipping_country" value="SE" required>
            <label>Ordernotering</label><textarea name="order_notes" rows="4" placeholder="T.ex. företagsnamn, referens eller önskemål"></textarea>
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
          <p><strong>Ordertotalt: <?= number_format((float) ($cartData['total_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></strong></p>
        </section>

        <section class="panel">
          <h3>Leverans, retur och hjälp</h3>
          <ul>
            <li>Leveransinformation: <a href="/pages/fraktinfo">Fraktinfo</a></li>
            <li>Returer/reklamation: <a href="/pages/retur-reklamation">Retur / reklamation</a></li>
            <li>Kontakt: <a href="/pages/kontakt">Kontakta oss</a></li>
            <li>Villkor: <a href="/pages/kopvillkor">Köpvillkor</a></li>
          </ul>
        </section>
      </aside>
    </div>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Checkout | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
