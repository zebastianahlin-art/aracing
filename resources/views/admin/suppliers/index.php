<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Leverantörer</h3><a class="btn" href="/admin/suppliers/create">+ Ny leverantör</a></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Namn</th><th>Slug</th><th>Aktiv</th><th>Kontakt</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($suppliers as $supplier): ?>
      <tr>
        <td><?= (int) $supplier['id'] ?></td>
        <td><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $supplier['slug'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="pill <?= (int) $supplier['is_active'] === 1 ? 'ok' : 'warn' ?>"><?= (int) $supplier['is_active'] === 1 ? 'Ja' : 'Nej' ?></span></td>
        <td><?= htmlspecialchars((string) ($supplier['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) ($supplier['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/suppliers/<?= (int) $supplier['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Leverantörer | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
