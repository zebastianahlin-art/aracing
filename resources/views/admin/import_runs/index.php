<?php ob_start(); ?>
<section class="card" style="margin-bottom:1rem;">
  <div class="topline"><h3>Starta CSV-import</h3></div>
  <?php if (is_string($error) && $error !== ''): ?>
    <p class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <form method="post" action="/admin/import-runs/upload" enctype="multipart/form-data">
    <div class="grid">
      <div>
        <label>Importprofil</label>
        <select required name="import_profile_id">
          <option value="">Välj profil</option>
          <?php foreach ($profiles as $profile): ?>
            <option value="<?= (int) $profile['id'] ?>"><?= htmlspecialchars((string) $profile['supplier_name'] . ' / ' . (string) $profile['name'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>CSV-fil</label>
        <input required type="file" name="csv_file" accept=".csv,text/csv">
      </div>
    </div>
    <br><button class="btn" type="submit">Starta import</button>
  </form>
</section>

<section class="card">
  <div class="topline"><h3>Importkörningar</h3></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Leverantör</th><th>Profil</th><th>Fil</th><th>Status</th><th>Rader</th><th>Start</th><th>Slut</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($runs as $run): ?>
      <tr>
        <td><?= (int) $run['id'] ?></td>
        <td><?= htmlspecialchars((string) ($run['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($run['profile_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($run['filename'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="pill <?= (string) $run['status'] === 'completed' ? 'ok' : ((string) $run['status'] === 'failed' ? 'bad' : 'warn') ?>"><?= htmlspecialchars((string) $run['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= (int) $run['success_rows'] ?> / <?= (int) $run['failed_rows'] ?> (tot: <?= (int) $run['total_rows'] ?>)</td>
        <td><?= htmlspecialchars((string) ($run['started_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($run['finished_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/import-runs/<?= (int) $run['id'] ?>">Detalj</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Importkörningar | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
