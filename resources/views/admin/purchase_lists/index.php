<?php ob_start(); ?>
<section class="card">
  <div class="topline">
    <h3>Inköpsunderlag</h3>
    <a class="btn" href="/admin/purchasing">+ Skapa från behovslista</a>
  </div>

  <?php if (($message ?? '') !== ''): ?>
    <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <table class="table compact">
    <thead>
    <tr>
      <th>ID</th><th>Namn</th><th>Status</th><th>Rader</th><th>Vald kvantitet</th><th>Uppdaterad</th><th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($lists ?? []) as $list): ?>
      <tr>
        <td><?= (int) $list['id'] ?></td>
        <td><?= htmlspecialchars((string) $list['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="pill"><?= htmlspecialchars((string) $list['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= (int) $list['item_count'] ?></td>
        <td><?= (int) ($list['total_selected_quantity'] ?? 0) ?></td>
        <td><?= htmlspecialchars((string) $list['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/purchase-lists/<?= (int) $list['id'] ?>">Öppna</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Inköpsunderlag | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
