<?php
$isEdit = is_array($page);
ob_start();
?>
<section class="card">
  <h1><?= $isEdit ? 'Redigera CMS-sida' : 'Skapa CMS-sida' ?></h1>
  <form method="post" action="<?= $isEdit ? '/admin/cms/pages/' . (int) $page['id'] : '/admin/cms/pages' ?>">
    <div class="grid">
      <div>
        <label>Titel</label>
        <input name="title" required value="<?= htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label>Slug</label>
        <input name="slug" value="<?= htmlspecialchars((string) ($page['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label>Sidtyp</label>
        <?php $type = (string) ($page['page_type'] ?? 'page'); ?>
        <select name="page_type">
          <option value="page" <?= $type === 'page' ? 'selected' : '' ?>>page</option>
          <option value="legal" <?= $type === 'legal' ? 'selected' : '' ?>>legal</option>
          <option value="info" <?= $type === 'info' ? 'selected' : '' ?>>info</option>
        </select>
      </div>
      <div>
        <label><input type="checkbox" name="is_active" value="1" <?= (int) ($page['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Aktiv</label>
      </div>
    </div>

    <label>Meta title</label>
    <input name="meta_title" value="<?= htmlspecialchars((string) ($page['meta_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Meta description</label>
    <textarea name="meta_description"><?= htmlspecialchars((string) ($page['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Innehåll (HTML)</label>
    <textarea name="content_html" style="min-height:240px;"><?= htmlspecialchars((string) ($page['content_html'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

    <p><button class="btn" type="submit">Spara</button> <a class="btn" href="/admin/cms/pages">Tillbaka</a></p>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = ($isEdit ? 'Redigera' : 'Skapa') . ' CMS-sida | Admin';
require __DIR__ . '/../../layouts/admin.php';
