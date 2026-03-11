<?php
ob_start();
$detail = $detail ?? null;
$case = $detail['case'] ?? null;
$history = $detail['history'] ?? [];
$statuses = $statuses ?? [];
$priorities = $priorities ?? [];
$statusLabels = $statusLabels ?? [];
$priorityLabels = $priorityLabels ?? [];
$sourceLabels = $sourceLabels ?? [];
$message = $message ?? '';
$error = $error ?? '';
?>
<section class="card">
  <?php if ($case === null): ?>
    <p class="error-box">Supportärendet hittades inte.</p>
  <?php else: ?>
    <h2><?= htmlspecialchars((string) $case['case_number'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string) $case['subject'], ENT_QUOTES, 'UTF-8') ?></h2>
    <?php if ($message !== ''): ?><p class="pill ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error !== ''): ?><p class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

    <div class="grid">
      <div>
        <h3>Kund</h3>
        <p><strong>Namn:</strong> <?= htmlspecialchars((string) ($case['name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>E-post:</strong> <?= htmlspecialchars((string) $case['email'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Telefon:</strong> <?= htmlspecialchars((string) ($case['phone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Källa:</strong> <?= htmlspecialchars((string) ($sourceLabels[$case['source']] ?? $case['source']), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Order:</strong> <?= htmlspecialchars((string) ($case['order_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div>
        <h3>Statushantering</h3>
        <form method="post" action="/admin/support-cases/<?= (int) $case['id'] ?>/status" style="margin-bottom:.6rem;">
          <label>Status</label>
          <select name="status">
            <?php foreach ($statuses as $status): ?>
              <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $case['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) ($statusLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn" type="submit">Uppdatera status</button>
        </form>

        <form method="post" action="/admin/support-cases/<?= (int) $case['id'] ?>/priority">
          <label>Prioritet</label>
          <select name="priority">
            <?php foreach ($priorities as $priority): ?>
              <option value="<?= htmlspecialchars($priority, ENT_QUOTES, 'UTF-8') ?>" <?= $case['priority'] === $priority ? 'selected' : '' ?>><?= htmlspecialchars((string) ($priorityLabels[$priority] ?? $priority), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn" type="submit">Uppdatera prioritet</button>
        </form>
      </div>
    </div>

    <h3>Kundens meddelande</h3>
    <p><?= nl2br(htmlspecialchars((string) $case['message'], ENT_QUOTES, 'UTF-8')) ?></p>

    <h3>Intern adminnotering</h3>
    <form method="post" action="/admin/support-cases/<?= (int) $case['id'] ?>/admin-note">
      <textarea name="admin_note"><?= htmlspecialchars((string) ($case['admin_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      <button class="btn" type="submit">Spara notering</button>
    </form>

    <h3>Historik</h3>
    <?php foreach ($history as $event): ?>
      <article class="timeline-item">
        <strong><?= htmlspecialchars((string) $event['event_type'], ENT_QUOTES, 'UTF-8') ?></strong>
        <div><?= htmlspecialchars((string) ($event['from_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> → <?= htmlspecialchars((string) ($event['to_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
        <div><?= nl2br(htmlspecialchars((string) ($event['comment'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
        <small><?= htmlspecialchars((string) $event['created_at'], ENT_QUOTES, 'UTF-8') ?></small>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Supportärende | Admin';
require __DIR__ . '/../layouts/admin.php';
