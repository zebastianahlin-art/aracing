<?php
$filters = $filters ?? ['name' => '', 'sku' => '', 'active' => '', 'has_supplier_link' => '', 'gap' => ''];
$gapLabels = [
    'missing_brand' => 'Saknar brand',
    'missing_category' => 'Saknar category',
    'missing_sale_price' => 'Saknar sale_price',
    'missing_description' => 'Saknar description',
    'missing_image' => 'Saknar bild',
    'missing_supplier_link' => 'Saknar leverantörskoppling',
    'inactive' => 'Inaktiv',
];
ob_start();
?>
<section class="card">
  <div class="topline"><h3>Artikelvårdskö v1</h3></div>

  <form method="get" action="/admin/products/article-care" class="grid-4" style="margin-bottom:.75rem;">
    <div><label>Sök namn</label><input name="name" value="<?= htmlspecialchars((string) $filters['name'], ENT_QUOTES, 'UTF-8') ?>"></div>
    <div><label>Sök SKU</label><input name="sku" value="<?= htmlspecialchars((string) $filters['sku'], ENT_QUOTES, 'UTF-8') ?>"></div>
    <div>
      <label>Aktiv</label>
      <select name="active">
        <option value="">Alla</option>
        <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Aktiv</option>
        <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Inaktiv</option>
      </select>
    </div>
    <div>
      <label>Leverantörskoppling</label>
      <select name="has_supplier_link">
        <option value="">Alla</option>
        <option value="1" <?= $filters['has_supplier_link'] === '1' ? 'selected' : '' ?>>Med koppling</option>
        <option value="0" <?= $filters['has_supplier_link'] === '0' ? 'selected' : '' ?>>Utan koppling</option>
      </select>
    </div>
    <div>
      <label>Bristtyp</label>
      <select name="gap">
        <option value="">Alla</option>
        <?php foreach ($gapLabels as $key => $label): ?>
          <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['gap'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex; gap:.5rem; align-items:flex-end;">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/products/article-care">Nollställ</a>
    </div>
  </form>

  <table class="table compact">
    <thead>
      <tr><th>ID</th><th>Produkt</th><th>Status</th><th>Brister</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= (int) $row['id'] ?></td>
        <td>
          <strong><?= htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
          <span class="muted">SKU: <?= htmlspecialchars((string) ($row['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td><span class="pill <?= (int) $row['is_active'] === 1 ? 'ok' : 'bad' ?>"><?= (int) $row['is_active'] === 1 ? 'Aktiv' : 'Inaktiv' ?></span></td>
        <td>
          <?php foreach (($row['care_gaps'] ?? []) as $gap): ?>
            <span class="pill warn"><?= htmlspecialchars((string) ($gapLabels[$gap] ?? $gap), ENT_QUOTES, 'UTF-8') ?></span>
          <?php endforeach; ?>
        </td>
        <td><a class="btn" href="/admin/products/<?= (int) $row['id'] ?>/edit">Redigera</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Artikelvårdskö | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
