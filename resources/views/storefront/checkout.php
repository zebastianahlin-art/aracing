<?php
ob_start();
$items = $cartData['items'] ?? [];
?>
<section class="panel">
  <h2>Checkout</h2>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <?php if ($items === []): ?>
    <p class="muted">Kundvagnen är tom. <a href="/">Gå till startsidan</a>.</p>
  <?php else: ?>
    <p class="muted">Ordertotalt: <?= number_format((float) ($cartData['total_amount'] ?? 0), 2, ',', ' ') ?> <?= htmlspecialchars((string) $cartData['cart']['currency_code'], ENT_QUOTES, 'UTF-8') ?></p>
    <form method="post" action="/checkout/place-order">
      <div class="grid-2">
        <div>
          <h3>Kund</h3>
          <label>Förnamn</label><input name="customer_first_name" required>
          <label>Efternamn</label><input name="customer_last_name" required>
          <label>E-post</label><input name="customer_email" type="email" required>
          <label>Telefon</label><input name="customer_phone">

          <h3>Fakturaadress</h3>
          <label>Adressrad 1</label><input name="billing_address_line_1" required>
          <label>Adressrad 2</label><input name="billing_address_line_2">
          <label>Postnummer</label><input name="billing_postal_code" required>
          <label>Stad</label><input name="billing_city" required>
          <label>Land (ISO2)</label><input name="billing_country" value="SE" required>
        </div>

        <div>
          <h3>Leveransadress</h3>
          <label>Förnamn</label><input name="shipping_first_name" required>
          <label>Efternamn</label><input name="shipping_last_name" required>
          <label>Telefon</label><input name="shipping_phone">
          <label>Adressrad 1</label><input name="shipping_address_line_1" required>
          <label>Adressrad 2</label><input name="shipping_address_line_2">
          <label>Postnummer</label><input name="shipping_postal_code" required>
          <label>Stad</label><input name="shipping_city" required>
          <label>Land (ISO2)</label><input name="shipping_country" value="SE" required>

          <label>Ordernotering</label><textarea name="order_notes" rows="4"></textarea>
        </div>
      </div>
      <button type="submit" class="btn-primary">Skapa order</button>
    </form>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Checkout | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
