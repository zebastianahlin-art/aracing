<?php
ob_start();
$customer = $customer ?? [];
$orderId = $orderId ?? null;
$error = $error ?? '';
$name = trim((string) (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')));
?>
<section class="panel">
  <h2>Skapa supportärende</h2>
  <?php if ($error !== ''): ?><p class="err-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <form method="post" action="<?= $orderId !== null ? '/account/orders/' . (int) $orderId . '/support' : '/account/support-cases' ?>">
    <?php if ($orderId !== null): ?>
      <p class="muted">Ärendet kommer kopplas till order #<?= (int) $orderId ?>.</p>
      <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
    <?php endif; ?>

    <div class="grid-2">
      <div><label>Namn</label><input type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>E-post *</label><input type="email" name="email" required value="<?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
    <div class="grid-2">
      <div><label>Telefon</label><input type="text" name="phone" value="<?= htmlspecialchars((string) ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Ämne *</label><input type="text" name="subject" required></div>
    </div>
    <label>Meddelande *</label>
    <textarea name="message" required></textarea>
    <p><button type="submit" class="btn-primary">Skapa ärende</button></p>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Skapa supportärende | A-Racing';
require __DIR__ . '/../../../layouts/storefront.php';
