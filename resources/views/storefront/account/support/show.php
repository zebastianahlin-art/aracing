<?php
ob_start();
$detail = $detail ?? null;
$case = $detail['case'] ?? null;
$history = $detail['history'] ?? [];
$statusLabels = $statusLabels ?? [];
$message = trim((string) ($_GET['message'] ?? ''));
?>
<section class="panel">
  <?php if ($case === null): ?>
    <p class="err-msg">Supportärendet kunde inte laddas.</p>
  <?php else: ?>
    <h2>Supportärende <?= htmlspecialchars((string) $case['case_number'], ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($message !== ''): ?><p class="ok-msg"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <p><strong>Ämne:</strong> <?= htmlspecialchars((string) $case['subject'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars((string) ($statusLabels[$case['status']] ?? $case['status']), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Skapat:</strong> <?= htmlspecialchars((string) $case['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (!empty($case['order_number'])): ?>
      <p><strong>Kopplad order:</strong> <?= htmlspecialchars((string) $case['order_number'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <h3>Ursprungligt meddelande</h3>
    <p><?= nl2br(htmlspecialchars((string) $case['message'], ENT_QUOTES, 'UTF-8')) ?></p>

    <h3>Historik</h3>
    <?php if ($history === []): ?>
      <p class="muted">Ingen historik ännu.</p>
    <?php else: ?>
      <?php foreach ($history as $event): ?>
        <article class="panel" style="margin-bottom:.5rem;">
          <strong><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></strong>
          <div><?= htmlspecialchars((string) ($event['from_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) ($event['to_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
          <div><?= nl2br(htmlspecialchars((string) ($event['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
          <small><?= htmlspecialchars((string) $event['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Supportärende | A-Racing';
require __DIR__ . '/../../../layouts/storefront.php';
