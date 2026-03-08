<?php
$isEdit = is_array($brand);
ob_start();
?>
<section class="card">
  <h3><?= $isEdit ? 'Redigera brand' : 'Skapa brand' ?></h3>
  <form method="post" action="<?= $isEdit ? '/admin/brands/' . (int) $brand['id'] : '/admin/brands' ?>">
    <label>Namn</label>
    <input name="name" required value="<?= htmlspecialchars((string) ($brand['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <label>Slug</label>
    <input name="slug" value="<?= htmlspecialchars((string) ($brand['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <br><br>
    <button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Brand-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
