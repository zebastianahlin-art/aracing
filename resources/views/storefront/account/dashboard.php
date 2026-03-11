<?php
ob_start();
$recentOrders = $recentOrders ?? [];
?>
<section class="panel">
  <h2>Mina sidor</h2>
  <p>Välkommen, <strong><?= htmlspecialchars((string) (($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></strong>.</p>
  <p class="muted">E-post: <?= htmlspecialchars((string) ($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p><a class="btn-secondary" href="/account/profile">Redigera profil</a> <a class="btn-secondary" href="/account/orders">Se alla ordrar</a></p>
</section>

<section class="panel" style="margin-top:.8rem;">
  <h3>Senaste ordrar</h3>
  <?php if ($recentOrders === []): ?>
    <p class="muted">Inga ordrar kopplade till ditt konto ännu.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Ordernr</th><th>Datum</th><th>Status</th><th>Totalsumma</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($recentOrders as $order): ?>
        <tr>
          <td><?= htmlspecialchars((string) $order['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $order['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $order['order_status'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= number_format((float) $order['total_amount'], 2, ',', ' ') ?> <?= htmlspecialchars((string) ($order['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/account/orders/<?= (int) $order['id'] ?>">Detaljer</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Mina sidor | A-Racing';
require __DIR__ . '/../../layouts/storefront.php';
