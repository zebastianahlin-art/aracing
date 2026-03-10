<?php
ob_start();
?>
<section class="card">
  <div class="topline"><h1>Kampanjkoder</h1><a class="btn" href="/admin/discount-codes/create">+ Ny kampanjkod</a></div>
  <?php if (($message ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <table class="table compact">
    <thead><tr><th>Code</th><th>Namn</th><th>Typ</th><th>Värde</th><th>Aktiv</th><th>Använd</th><th>Start</th><th>Slut</th><th></th></tr></thead>
    <tbody>
    <?php foreach (($codes ?? []) as $code): ?>
      <tr>
        <td><?= htmlspecialchars((string) $code['code'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $code['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $code['discount_type'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= number_format((float) $code['discount_value'], 2, ',', ' ') ?></td>
        <td><?= (int) $code['is_active'] === 1 ? 'Ja' : 'Nej' ?></td>
        <td><?= (int) $code['usage_count'] ?><?= ($code['usage_limit'] !== null) ? ' / ' . (int) $code['usage_limit'] : '' ?></td>
        <td><?= htmlspecialchars((string) ($code['starts_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($code['ends_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/discount-codes/<?= (int) $code['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kampanjkoder | Admin';
require __DIR__ . '/../../layouts/admin.php';
