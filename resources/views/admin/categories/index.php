<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Categories</h3><a class="btn" href="/admin/categories/create">+ Ny kategori</a></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Namn</th><th>Slug</th><th>Parent</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($categories as $category): ?>
      <tr>
        <td><?= (int) $category['id'] ?></td>
        <td><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($category['parent_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/categories/<?= (int) $category['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Categories | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
