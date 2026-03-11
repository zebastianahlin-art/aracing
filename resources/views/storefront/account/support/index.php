<?php
ob_start();
$cases = $cases ?? [];
$statusLabels = $statusLabels ?? [];
$error = $error ?? '';
$message = $message ?? '';
?>
<section class="panel">
  <h2>Mina supportärenden</h2>
  <p><a class="btn-secondary" href="/account/support-cases/create">Skapa nytt supportärende</a></p>
  <?php if ($error !== ''): ?><p class="err-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($message !== ''): ?><p class="ok-msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <?php if ($cases === []): ?>
    <p class="muted">Du har inga supportärenden ännu.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Ärendenummer</th><th>Ämne</th><th>Status</th><th>Skapat</th><th>Order</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cases as $case): ?>
        <tr>
          <td><?= htmlspecialchars((string) $case['case_number'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $case['subject'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($statusLabels[$case['status']] ?? $case['status']), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $case['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($case['order_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><a href="/account/support-cases/<?= (int) $case['id'] ?>">Visa</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Mina supportärenden | A-Racing';
require __DIR__ . '/../../../layouts/storefront.php';
