<?php
$method = is_array($method ?? null) ? $method : null;
$isEdit = $method !== null;
ob_start();
?>
<section class="card">
  <h1><?= $isEdit ? 'Redigera fraktmetod' : 'Skapa fraktmetod' ?></h1>
  <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="post" action="<?= $isEdit ? '/admin/shipping-methods/' . (int) $method['id'] : '/admin/shipping-methods' ?>">
    <label>Code *</label>
    <input type="text" name="code" required value="<?= htmlspecialchars((string) ($method['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Namn *</label>
    <input type="text" name="name" required value="<?= htmlspecialchars((string) ($method['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Beskrivning</label>
    <textarea name="description"><?= htmlspecialchars((string) ($method['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Pris ex moms</label>
    <input type="number" step="0.01" min="0" name="price_ex_vat" value="<?= htmlspecialchars((string) ($method['price_ex_vat'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">

    <label>Pris inkl moms</label>
    <input type="number" step="0.01" min="0" name="price_inc_vat" value="<?= htmlspecialchars((string) ($method['price_inc_vat'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">

    <label>Sortering</label>
    <input type="number" name="sort_order" value="<?= (int) ($method['sort_order'] ?? 0) ?>">

    <label>Aktiv</label>
    <select name="is_active">
      <option value="1" <?= ((int) ($method['is_active'] ?? 1) === 1) ? 'selected' : '' ?>>Ja</option>
      <option value="0" <?= ((int) ($method['is_active'] ?? 1) === 0) ? 'selected' : '' ?>>Nej</option>
    </select>

    <br><br>
    <button type="submit" class="btn">Spara</button>
    <a class="btn" href="/admin/shipping-methods">Avbryt</a>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Fraktmetod | Admin';
require __DIR__ . '/../../layouts/admin.php';
