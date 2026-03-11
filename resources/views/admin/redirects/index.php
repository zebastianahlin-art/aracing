<?php declare(strict_types=1); ?>
<?php ob_start(); ?>
<div class="card">
  <div class="topline">
    <h3>Redirects</h3>
    <a class="btn" href="/admin/redirects/create">+ Ny redirect</a>
  </div>

  <?php if (!empty($message)): ?>
    <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/redirects" class="grid" style="margin-bottom:.75rem;">
    <div>
      <label for="is_active">Status</label>
      <select id="is_active" name="is_active">
        <option value="" <?= ($filters['is_active'] ?? '') === '' ? 'selected' : '' ?>>Alla</option>
        <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Aktiva</option>
        <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inaktiva</option>
      </select>
    </div>
    <div>
      <label>&nbsp;</label>
      <button class="btn" type="submit">Filtrera</button>
    </div>
  </form>

  <table class="table compact">
    <thead>
      <tr>
        <th>Source path</th>
        <th>Target path</th>
        <th>Typ</th>
        <th>Aktiv</th>
        <th>Träffar</th>
        <th>Senast träff</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($redirects as $row): ?>
        <tr>
          <td><code><?= htmlspecialchars((string) $row['source_path'], ENT_QUOTES, 'UTF-8') ?></code></td>
          <td><code><?= htmlspecialchars((string) $row['target_path'], ENT_QUOTES, 'UTF-8') ?></code></td>
          <td><?= (int) $row['redirect_type'] ?></td>
          <td><?= (int) $row['is_active'] === 1 ? 'Ja' : 'Nej' ?></td>
          <td><?= (int) $row['hit_count'] ?></td>
          <td><?= htmlspecialchars((string) ($row['last_hit_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><a class="btn" href="/admin/redirects/<?= (int) $row['id'] ?>/edit">Redigera</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($redirects === []): ?>
        <tr><td colspan="7">Inga redirects hittades.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php $content = (string) ob_get_clean(); $title = 'Redirects | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
