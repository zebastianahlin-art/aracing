<?php
$isEdit = is_array($profile);
$defaultMapping = "{\n  \"supplier_sku\": \"Artikelnummer\",\n  \"supplier_title\": \"Benamning\",\n  \"price\": \"Pris\",\n  \"stock_qty\": \"Lagersaldo\"\n}";
ob_start();
?>
<section class="card">
  <h3><?= $isEdit ? 'Redigera importprofil' : 'Skapa importprofil' ?></h3>
  <form method="post" action="<?= $isEdit ? '/admin/import-profiles/' . (int) $profile['id'] : '/admin/import-profiles' ?>">
    <div class="grid">
      <div>
        <label>Leverantör</label>
        <select required name="supplier_id">
          <option value="">Välj leverantör</option>
          <?php foreach ($suppliers as $supplier): ?>
            <option value="<?= (int) $supplier['id'] ?>" <?= (string) ($profile['supplier_id'] ?? '') === (string) $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Namn</label><input required name="name" value="<?= htmlspecialchars((string) ($profile['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Filtyp</label><input name="file_type" value="<?= htmlspecialchars((string) ($profile['file_type'] ?? 'csv'), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Delimiter</label><input name="delimiter" value="<?= htmlspecialchars((string) ($profile['delimiter'] ?? ','), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Enclosure</label><input name="enclosure" value="<?= htmlspecialchars((string) ($profile['enclosure'] ?? '"'), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Escape</label><input name="escape_char" value="<?= htmlspecialchars((string) ($profile['escape_char'] ?? '\\'), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?= (int) ($profile['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Aktiv</label></div>
    </div>
    <label>Column mapping JSON</label>
    <textarea name="column_mapping_json"><?= htmlspecialchars((string) ($profile['column_mapping_json'] ?? $defaultMapping), ENT_QUOTES, 'UTF-8') ?></textarea>
    <br><button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Importprofil-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
