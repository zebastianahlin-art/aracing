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
    <br><br><button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Category-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
