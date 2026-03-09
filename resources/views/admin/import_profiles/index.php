<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Importprofiler</h3><a class="btn" href="/admin/import-profiles/create">+ Ny profil</a></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Leverantör</th><th>Namn</th><th>Filtyp</th><th>Delimiter</th><th>Aktiv</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($profiles as $profile): ?>
      <tr>
        <td><?= (int) $profile['id'] ?></td>
        <td><?= htmlspecialchars((string) $profile['supplier_name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $profile['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $profile['file_type'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $profile['delimiter'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="pill <?= (int) $profile['is_active'] === 1 ? 'ok' : 'warn' ?>"><?= (int) $profile['is_active'] === 1 ? 'Ja' : 'Nej' ?></span></td>
        <td><a class="btn" href="/admin/import-profiles/<?= (int) $profile['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Importprofiler | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
