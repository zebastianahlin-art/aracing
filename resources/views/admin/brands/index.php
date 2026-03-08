<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Brands</h3><a class="btn" href="/admin/brands/create">+ Ny brand</a></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Namn</th><th>Slug</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($brands as $brand): ?>
      <tr>
        <td><?= (int) $brand['id'] ?></td>
        <td><?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $brand['slug'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/brands/<?= (int) $brand['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Brands | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
