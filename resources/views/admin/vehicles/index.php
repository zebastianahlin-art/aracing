<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Fordon (YMM v1)</h3><a class="btn" href="/admin/vehicles/create">+ Nytt fordon</a></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Make / modell</th><th>Generation / motor</th><th>År</th><th>Aktiv</th><th>Sort</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($vehicles as $vehicle): ?>
      <tr>
        <td><?= (int) $vehicle['id'] ?></td>
        <td><strong><?= htmlspecialchars((string) $vehicle['make'], ENT_QUOTES, 'UTF-8') ?></strong><br><?= htmlspecialchars((string) $vehicle['model'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) (($vehicle['generation'] ?? '-') . ' / ' . ($vehicle['engine'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars(trim((string) ($vehicle['year_from'] ?? '')) . '–' . trim((string) ($vehicle['year_to'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int) $vehicle['is_active'] === 1 ? 'Ja' : 'Nej' ?></td>
        <td><?= (int) $vehicle['sort_order'] ?></td>
        <td><a class="btn" href="/admin/vehicles/<?= (int) $vehicle['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Fordon | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
