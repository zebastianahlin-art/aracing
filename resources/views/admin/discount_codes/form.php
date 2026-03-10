<?php
$codeEntity = is_array($codeEntity ?? null) ? $codeEntity : null;
$isEdit = $codeEntity !== null;
ob_start();
?>
<section class="card">
  <h1><?= $isEdit ? 'Redigera kampanjkod' : 'Skapa kampanjkod' ?></h1>
  <?php if (($error ?? '') !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="post" action="<?= $isEdit ? '/admin/discount-codes/' . (int) $codeEntity['id'] : '/admin/discount-codes' ?>">
    <label>Code *</label>
    <input type="text" name="code" required maxlength="80" value="<?= htmlspecialchars((string) ($codeEntity['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Namn *</label>
    <input type="text" name="name" required maxlength="120" value="<?= htmlspecialchars((string) ($codeEntity['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Beskrivning</label>
    <textarea name="description"><?= htmlspecialchars((string) ($codeEntity['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Rabatt-typ *</label>
    <select name="discount_type">
      <option value="fixed_amount" <?= (($codeEntity['discount_type'] ?? 'fixed_amount') === 'fixed_amount') ? 'selected' : '' ?>>Fast belopp</option>
      <option value="percent" <?= (($codeEntity['discount_type'] ?? '') === 'percent') ? 'selected' : '' ?>>Procent</option>
    </select>

    <label>Rabattvärde *</label>
    <input type="number" step="0.01" min="0" name="discount_value" value="<?= htmlspecialchars((string) ($codeEntity['discount_value'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">

    <label>Minsta ordersumma</label>
    <input type="number" step="0.01" min="0" name="minimum_order_amount" value="<?= htmlspecialchars((string) ($codeEntity['minimum_order_amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Användningsgräns</label>
    <input type="number" min="1" name="usage_limit" value="<?= htmlspecialchars((string) ($codeEntity['usage_limit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Start (YYYY-MM-DD HH:MM:SS)</label>
    <input type="text" name="starts_at" value="<?= htmlspecialchars((string) ($codeEntity['starts_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Slut (YYYY-MM-DD HH:MM:SS)</label>
    <input type="text" name="ends_at" value="<?= htmlspecialchars((string) ($codeEntity['ends_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Sortering</label>
    <input type="number" name="sort_order" value="<?= (int) ($codeEntity['sort_order'] ?? 0) ?>">

    <label>Aktiv</label>
    <select name="is_active">
      <option value="1" <?= ((int) ($codeEntity['is_active'] ?? 1) === 1) ? 'selected' : '' ?>>Ja</option>
      <option value="0" <?= ((int) ($codeEntity['is_active'] ?? 1) === 0) ? 'selected' : '' ?>>Nej</option>
    </select>

    <br><br>
    <button type="submit" class="btn">Spara</button>
    <a class="btn" href="/admin/discount-codes">Avbryt</a>
  </form>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kampanjkod | Admin';
require __DIR__ . '/../../layouts/admin.php';
