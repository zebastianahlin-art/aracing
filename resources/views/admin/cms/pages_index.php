<?php
ob_start();
?>
<section class="card">
  <div class="topline">
    <h1>CMS-sidor</h1>
    <a class="btn" href="/admin/cms/pages/create">Ny sida</a>
  </div>
  <table class="table compact">
    <thead>
      <tr><th>ID</th><th>Titel</th><th>Slug</th><th>Typ</th><th>Status</th><th>Uppdaterad</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($pages as $page): ?>
      <tr>
        <td>#<?= (int) $page['id'] ?></td>
        <td><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></td>
        <td>/pages/<?= htmlspecialchars((string) $page['slug'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $page['page_type'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int) $page['is_active'] === 1 ? '<span class="pill ok">aktiv</span>' : '<span class="pill bad">inaktiv</span>' ?></td>
        <td><?= htmlspecialchars((string) $page['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/cms/pages/<?= (int) $page['id'] ?>/edit">Redigera</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if ($pages === []): ?><tr><td colspan="7">Inga CMS-sidor ännu.</td></tr><?php endif; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'CMS-sidor | Admin';
require __DIR__ . '/../../layouts/admin.php';
