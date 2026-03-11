<?php
$isEdit = is_array($vehicle);
ob_start();
?>
<section class="card">
  <h3><?= $isEdit ? 'Redigera fordon' : 'Skapa fordon' ?></h3>
  <form method="post" action="<?= $isEdit ? '/admin/vehicles/' . (int) $vehicle['id'] : '/admin/vehicles' ?>">
    <div class="grid">
      <div><label>Make</label><input name="make" required value="<?= htmlspecialchars((string) ($vehicle['make'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Modell</label><input name="model" required value="<?= htmlspecialchars((string) ($vehicle['model'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Generation</label><input name="generation" value="<?= htmlspecialchars((string) ($vehicle['generation'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Motor</label><input name="engine" value="<?= htmlspecialchars((string) ($vehicle['engine'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Bränsletyp</label><input name="fuel_type" value="<?= htmlspecialchars((string) ($vehicle['fuel_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Karosstyp</label><input name="body_type" value="<?= htmlspecialchars((string) ($vehicle['body_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>År från</label><input type="number" name="year_from" value="<?= htmlspecialchars((string) ($vehicle['year_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>År till</label><input type="number" name="year_to" value="<?= htmlspecialchars((string) ($vehicle['year_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Sortering</label><input type="number" name="sort_order" value="<?= (int) ($vehicle['sort_order'] ?? 0) ?>"></div>
    </div>
    <label style="margin-top:.5rem;"><input type="checkbox" name="is_active" value="1" <?= (int) ($vehicle['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Aktiv i storefront</label>
    <br><br>
    <button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Fordon-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
