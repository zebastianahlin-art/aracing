<?php ob_start(); ?>
<section class="card" style="margin-bottom:1rem;">
  <div class="topline">
    <h3>Importkörning #<?= (int) ($run['id'] ?? 0) ?></h3>
    <a class="btn" href="/admin/import-runs">Tillbaka</a>
  </div>
  <?php if (!is_array($run)): ?>
    <p>Körningen kunde inte hittas.</p>
  <?php else: ?>
    <div class="grid">
      <div><label>Leverantör</label><div><?= htmlspecialchars((string) ($run['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
      <div><label>Profil</label><div><?= htmlspecialchars((string) ($run['profile_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
      <div><label>Status</label><div><span class="pill <?= (string) $run['status'] === 'completed' ? 'ok' : ((string) $run['status'] === 'failed' ? 'bad' : 'warn') ?>"><?= htmlspecialchars((string) $run['status'], ENT_QUOTES, 'UTF-8') ?></span></div></div>
      <div><label>Fil</label><div><?= htmlspecialchars((string) ($run['filename'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
      <div><label>Total rows</label><div><?= (int) $run['total_rows'] ?></div></div>
      <div><label>Processed rows</label><div><?= (int) $run['processed_rows'] ?></div></div>
      <div><label>Success rows</label><div><?= (int) $run['success_rows'] ?></div></div>
      <div><label>Failed rows</label><div><?= (int) $run['failed_rows'] ?></div></div>
      <div><label>Started</label><div><?= htmlspecialchars((string) ($run['started_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
      <div><label>Finished</label><div><?= htmlspecialchars((string) ($run['finished_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></div>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <div class="topline"><h3>Importrader</h3></div>
  <table class="table">
    <thead><tr><th>#</th><th>Status</th><th>Fel</th><th>Rådata</th><th>Mappad data</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= (int) $row['row_number'] ?></td>
        <td><span class="pill <?= (string) $row['status'] === 'success' ? 'ok' : 'bad' ?>"><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= htmlspecialchars((string) ($row['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><pre><?= htmlspecialchars((string) $row['raw_row_json'], ENT_QUOTES, 'UTF-8') ?></pre></td>
        <td><pre><?= htmlspecialchars((string) ($row['mapped_row_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Import run | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
