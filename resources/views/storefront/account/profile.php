<?php
ob_start();
?>
<section class="panel" style="max-width:680px;">
  <h2>Profil</h2>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="post" action="/account/profile">
    <label>Förnamn</label>
    <input type="text" name="first_name" required value="<?= htmlspecialchars((string) ($customer['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Efternamn</label>
    <input type="text" name="last_name" required value="<?= htmlspecialchars((string) ($customer['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Telefon</label>
    <input type="text" name="phone" value="<?= htmlspecialchars((string) ($customer['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>E-post</label>
    <input type="email" disabled value="<?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <p style="margin-top:.9rem;"><button class="btn-primary" type="submit">Spara profil</button> <a class="btn-secondary" href="/account/address">Hantera adress</a></p>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Profil | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
