<?php
ob_start();
?>
<section class="panel">
  <h2>Tack för din order</h2>
  <?php if ($orderNumber === null): ?>
    <p class="muted">Ingen ny order hittades i sessionen.</p>
  <?php else: ?>
    <p>Ordernummer: <strong><?= htmlspecialchars((string) $orderNumber, ENT_QUOTES, 'UTF-8') ?></strong></p>
    <p class="muted">Din order är registrerad som pending. Betalning och frakt hanteras i v1 som placeholders.</p>
  <?php endif; ?>
  <p><a class="btn-primary" href="/">Till startsidan</a></p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Orderbekräftelse | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
