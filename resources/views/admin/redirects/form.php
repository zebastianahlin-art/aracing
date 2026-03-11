<?php declare(strict_types=1); ?>
<?php
$isEdit = is_array($redirect);
$sourcePath = $isEdit ? (string) ($redirect['source_path'] ?? '') : '';
$targetPath = $isEdit ? (string) ($redirect['target_path'] ?? '') : '';
$redirectType = $isEdit ? (int) ($redirect['redirect_type'] ?? 301) : 301;
$isActive = $isEdit ? (int) ($redirect['is_active'] ?? 1) === 1 : true;
$notes = $isEdit ? (string) ($redirect['notes'] ?? '') : '';
?>
<?php ob_start(); ?>
<div class="card">
  <h3><?= $isEdit ? 'Redigera redirect' : 'Skapa redirect' ?></h3>

  <?php if (!empty($error)): ?>
    <p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="<?= $isEdit ? '/admin/redirects/' . (int) $redirect['id'] : '/admin/redirects' ?>">
    <label for="source_path">Source path (intern)</label>
    <input id="source_path" type="text" name="source_path" value="<?= htmlspecialchars($sourcePath, ENT_QUOTES, 'UTF-8') ?>" placeholder="/gammal-url" required>

    <label for="target_path">Target path (intern)</label>
    <input id="target_path" type="text" name="target_path" value="<?= htmlspecialchars($targetPath, ENT_QUOTES, 'UTF-8') ?>" placeholder="/ny-url" required>

    <div class="grid">
      <div>
        <label for="redirect_type">Redirect-typ</label>
        <select id="redirect_type" name="redirect_type">
          <option value="301" <?= $redirectType === 301 ? 'selected' : '' ?>>301 (permanent)</option>
          <option value="302" <?= $redirectType === 302 ? 'selected' : '' ?>>302 (temporär)</option>
        </select>
      </div>
      <div>
        <label for="is_active">Aktiv</label>
        <input id="is_active" type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
      </div>
    </div>

    <label for="notes">Notering</label>
    <textarea id="notes" name="notes" placeholder="Frivillig notering om varför redirecten finns."><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>

    <p><button class="btn" type="submit">Spara</button> <a class="btn" href="/admin/redirects">Tillbaka</a></p>
  </form>
</div>
<?php $content = (string) ob_get_clean(); $title = ($isEdit ? 'Redigera' : 'Skapa') . ' redirect | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
