<?php
ob_start();
$customer = $customer ?? null;
$error = $error ?? '';
$message = $message ?? '';
$name = $customer !== null ? trim((string) (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) : '';
$email = $customer !== null ? (string) ($customer['email'] ?? '') : '';
$phone = $customer !== null ? (string) ($customer['phone'] ?? '') : '';
?>
<section class="panel">
  <h2>Kontakta support</h2>
  <p class="muted">Skicka ett supportärende till oss. Vi återkommer så snart vi kan.</p>
  <?php if ($error !== ''): ?><p class="err-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($message !== ''): ?><p class="ok-msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="post" action="/contact">
    <div class="grid-2">
      <div><label>Namn</label><input type="text" name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>E-post *</label><input type="email" name="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"></div>
    </div>
    <div class="grid-2">
      <div><label>Telefon</label><input type="text" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Ämne *</label><input type="text" name="subject" required></div>
    </div>
    <label>Meddelande *</label>
    <textarea name="message" required></textarea>
    <p><button type="submit" class="btn-primary">Skapa supportärende</button></p>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kontakta support | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
