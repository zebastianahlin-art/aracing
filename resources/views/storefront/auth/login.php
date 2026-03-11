<?php
ob_start();
?>
<section class="panel" style="max-width:520px;margin:0 auto;">
  <h2>Logga in</h2>
  <p class="muted">Logga in för att se dina ordrar och hantera Mina sidor.</p>
  <?php if (($error ?? '') !== ''): ?>
    <p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="/login">
    <label>E-post</label>
    <input type="email" name="email" required>

    <label>Lösenord</label>
    <input type="password" name="password" required>

    <p style="margin-top:.9rem;"><button class="btn-primary" type="submit">Logga in</button></p>
  </form>

  <p class="muted">Har du inget konto? <a href="/register">Registrera dig</a>.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Logga in | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
