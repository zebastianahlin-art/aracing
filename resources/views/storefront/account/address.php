<?php
ob_start();
?>
<section class="panel" style="max-width:680px;">
  <h2>Min adress</h2>
  <p class="muted">Den här adressen används som standard för förifyllning i checkout när du är inloggad.</p>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="post" action="/account/address">
    <label>Adressrad 1</label>
    <input type="text" name="address_line_1" value="<?= htmlspecialchars((string) ($customer['address_line_1'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Adressrad 2</label>
    <input type="text" name="address_line_2" value="<?= htmlspecialchars((string) ($customer['address_line_2'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Postnummer</label>
    <input type="text" name="postal_code" value="<?= htmlspecialchars((string) ($customer['postal_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Stad</label>
    <input type="text" name="city" value="<?= htmlspecialchars((string) ($customer['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Landkod (ISO2, t.ex. SE)</label>
    <input type="text" name="country_code" value="<?= htmlspecialchars((string) ($customer['country_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <p class="muted" style="margin-top:.5rem;">Lämna samtliga fält tomma och spara om du vill rensa din sparade standardadress.</p>

    <p style="margin-top:.9rem;"><button class="btn-primary" type="submit">Spara adress</button></p>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Min adress | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
