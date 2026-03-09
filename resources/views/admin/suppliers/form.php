<?php $isEdit = is_array($supplier); ob_start(); ?>
<section class="card">
  <h3><?= $isEdit ? 'Redigera leverantör' : 'Skapa leverantör' ?></h3>
  <form method="post" action="<?= $isEdit ? '/admin/suppliers/' . (int) $supplier['id'] : '/admin/suppliers' ?>">
    <div class="grid">
      <div><label>Namn</label><input required name="name" value="<?= htmlspecialchars((string) ($supplier['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Slug</label><input name="slug" value="<?= htmlspecialchars((string) ($supplier['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Kontaktperson</label><input name="contact_name" value="<?= htmlspecialchars((string) ($supplier['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>E-post</label><input name="contact_email" value="<?= htmlspecialchars((string) ($supplier['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?= (int) ($supplier['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Aktiv</label></div>
    </div>
    <label>Noteringar</label>
    <textarea name="notes"><?= htmlspecialchars((string) ($supplier['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    <br><button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Leverantör-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
