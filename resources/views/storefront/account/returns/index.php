<?php
ob_start();
$returns = $returns ?? [];
$statusLabels = $statusLabels ?? [];
?>
<section class="panel">
  <h2>Mina returer</h2>
  <?php if (($message ?? '') !== ''): ?><p class="ok-msg"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="err-msg"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <?php if ($returns === []): ?>
    <p class="muted">Du har inga returärenden ännu.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>RMA</th><th>Ordernummer</th><th>Status</th><th>Skapad</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($returns as $return): ?>
        <tr>
          <td><?= htmlspecialchars((string) $return['return_number'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $return['order_number'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($statusLabels[$return['status']] ?? $return['status']), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $return['requested_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/account/returns/<?= (int) $return['id'] ?>">Visa</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Mina returer | A-Racing';
require __DIR__ . '/../../../layouts/storefront.php';
