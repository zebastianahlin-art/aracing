<?php
$isEdit = is_array($category);
$currentParent = $category['parent_id'] ?? null;
ob_start();
?>
<section class="card">
  <h3><?= $isEdit ? 'Redigera kategori' : 'Skapa kategori' ?></h3>
  <form method="post" action="<?= $isEdit ? '/admin/categories/' . (int) $category['id'] : '/admin/categories' ?>">
    <label>Namn</label><input name="name" required value="<?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <label>Slug</label><input name="slug" value="<?= htmlspecialchars((string) ($category['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <label>Parent</label>
    <select name="parent_id">
      <option value="">Ingen</option>
      <?php foreach ($parentOptions as $option): ?>
        <?php if ($isEdit && (int) $option['id'] === (int) $category['id']) { continue; } ?>
        <option value="<?= (int) $option['id'] ?>" <?= (string) $currentParent === (string) $option['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string) $option['name'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>

    <h4>SEO (v1)</h4>
    <label>SEO-titel</label><input name="seo_title" maxlength="255" value="<?= htmlspecialchars((string) ($category['seo_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <label>SEO-beskrivning</label><textarea name="seo_description" maxlength="1000"><?= htmlspecialchars((string) ($category['seo_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    <label>Canonical URL (valfri override)</label><input name="canonical_url" maxlength="255" placeholder="/category/exempel eller https://..." value="<?= htmlspecialchars((string) ($category['canonical_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <?php $metaRobots = (string) ($category['meta_robots'] ?? ''); ?>
    <label>Meta robots override</label>
    <select name="meta_robots">
      <option value="">Automatisk</option>
      <option value="index,follow" <?= $metaRobots === 'index,follow' ? 'selected' : '' ?>>index,follow</option>
      <option value="noindex,follow" <?= $metaRobots === 'noindex,follow' ? 'selected' : '' ?>>noindex,follow</option>
    </select>
    <label><input type="checkbox" name="is_indexable" value="1" <?= (int) ($category['is_indexable'] ?? 1) === 1 ? 'checked' : '' ?>> Indexerbar kategori</label>

    <br><br><button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Category-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
