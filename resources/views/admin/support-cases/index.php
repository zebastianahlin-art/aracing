<?php
ob_start();
$cases = $cases ?? [];
$filters = $filters ?? [];
$statuses = $statuses ?? [];
$sources = $sources ?? [];
$statusLabels = $statusLabels ?? [];
$priorityLabels = $priorityLabels ?? [];
$sourceLabels = $sourceLabels ?? [];
?>
<section class="card">
  <h2>Supportärenden</h2>

  <form method="get" class="grid-3" style="margin-bottom:.8rem;">
    <div>
      <label>Status</label>
      <select name="status">
        <option value="">Alla</option>
        <?php foreach ($statuses as $status): ?>
          <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) ($statusLabels[$status] ?? $status), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Källa</label>
      <select name="source">
        <option value="">Alla</option>
        <?php foreach ($sources as $source): ?>
          <option value="<?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['source'] ?? '') === $source ? 'selected' : '' ?>><?= htmlspecialchars((string) ($sourceLabels[$source] ?? $source), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><button class="btn" type="submit">Filtrera</button></div>
  </form>

  <table class="table">
    <thead><tr><th>Case</th><th>Ämne</th><th>E-post</th><th>Status</th><th>Prioritet</th><th>Källa</th><th>Skapad</th><th>Order</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($cases as $case): ?>
      <tr>
        <td><?= htmlspecialchars((string) $case['case_number'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $case['subject'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $case['email'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($statusLabels[$case['status']] ?? $case['status']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($priorityLabels[$case['priority']] ?? $case['priority']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($sourceLabels[$case['source']] ?? $case['source']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $case['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($case['order_number'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/support-cases/<?= (int) $case['id'] ?>">Öppna</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Supportärenden | Admin';
require __DIR__ . '/../layouts/admin.php';
