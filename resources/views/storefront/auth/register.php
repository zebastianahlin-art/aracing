<?php
ob_start();
?>
<section class="panel" style="max-width:620px;margin:0 auto;">
  <h2>Skapa konto</h2>
  <p class="muted">Skapa ett kundkonto för att samla dina ordrar under Mina sidor.</p>
  <?php if (($error ?? '') !== ''): ?>
    <p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="/register">
    <label>Förnamn</label>
    <input type="text" name="first_name" required>

    <label>Efternamn</label>
    <input type="text" name="last_name" required>

    <label>E-post</label>
    <input type="email" name="email" required>

    <label>Telefon (valfritt)</label>
    <input type="text" name="phone">

    <label>Lösenord</label>
    <input type="password" name="password" minlength="8" required>

    <p style="margin-top:.9rem;"><button class="btn-primary" type="submit">Skapa konto</button></p>
  </form>

  <p class="muted">Har du redan konto? <a href="/login">Logga in</a>.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Registrering | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
