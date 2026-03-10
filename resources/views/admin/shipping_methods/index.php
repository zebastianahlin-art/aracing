<?php
ob_start();
?>
<section class="card">
  <div class="topline"><h1>Fraktmetoder</h1><a class="btn" href="/admin/shipping-methods/create">+ Ny fraktmetod</a></div>
  <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <table class="table compact">
    <thead><tr><th>Code</th><th>Namn</th><th>Pris inkl moms</th><th>Aktiv</th><th>Sortering</th><th></th></tr></thead>
    <tbody>
    <?php foreach (($methods ?? []) as $method): ?>
      <tr>
        <td><?= htmlspecialchars((string) $method['code'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $method['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= number_format((float) $method['price_inc_vat'], 2, ',', ' ') ?></td>
        <td><?= (int) $method['is_active'] === 1 ? 'Ja' : 'Nej' ?></td>
        <td><?= (int) $method['sort_order'] ?></td>
        <td><a class="btn" href="/admin/shipping-methods/<?= (int) $method['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Fraktmetoder | Admin';
require __DIR__ . '/../../layouts/admin.php';
