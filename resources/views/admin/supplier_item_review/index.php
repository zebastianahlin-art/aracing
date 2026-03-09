<?php ob_start();
/** @var array<int, array<string, mixed>> $rows */
/** @var array<string, mixed> $filters */
/** @var array<string, int> $quality */
/** @var array<int, array<string, mixed>> $suppliers */
/** @var array<int, array<string, mixed>> $runs */
/** @var array<int, array<string, mixed>> $products */
$returnQuery = http_build_query([
  'supplier_sku' => (string) ($filters['supplier_sku'] ?? ''),
  'supplier_title' => (string) ($filters['supplier_title'] ?? ''),
  'supplier_id' => (string) ($filters['supplier_id'] ?? ''),
  'match_status' => (string) ($filters['match_status'] ?? ''),
  'import_run_id' => (string) ($filters['import_run_id'] ?? ''),
  'data_gap' => (string) ($filters['data_gap'] ?? ''),
  'product_query' => (string) ($filters['product_query'] ?? ''),
]);
$gapLabels = [
  'missing_title' => 'Saknar titel',
  'missing_sku' => 'Saknar SKU',
  'missing_price' => 'Saknar pris',
  'missing_stock' => 'Saknar lager',
  'missing_product_link' => 'Saknar produktkoppling',
];
$statusLabels = [
  'unmatched' => 'Omatchad',
  'linked' => 'Kopplad',
  'needs_review' => 'Behöver granskning',
];
?>
<section class="card" style="margin-bottom:1rem;">
  <div class="topline"><h3>Importgranskning & matchningskö</h3></div>
  <?php if ($notice !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="get" action="/admin/supplier-item-review" class="grid-4">
    <div><label>Sök supplier_sku</label><input name="supplier_sku" value="<?= htmlspecialchars((string) ($filters['supplier_sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
    <div><label>Sök supplier_title</label><input name="supplier_title" value="<?= htmlspecialchars((string) ($filters['supplier_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
    <div><label>Leverantör</label>
      <select name="supplier_id">
        <option value="">Alla</option>
        <?php foreach ($suppliers as $supplier): ?>
          <option value="<?= (int) $supplier['id'] ?>" <?= (string) ($filters['supplier_id'] ?? '') === (string) $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Matchningsstatus</label>
      <select name="match_status">
        <option value="">Alla</option>
        <option value="unmatched" <?= (string) ($filters['match_status'] ?? '') === 'unmatched' ? 'selected' : '' ?>>Omatchad</option>
        <option value="linked" <?= (string) ($filters['match_status'] ?? '') === 'linked' ? 'selected' : '' ?>>Kopplad</option>
        <option value="needs_review" <?= (string) ($filters['match_status'] ?? '') === 'needs_review' ? 'selected' : '' ?>>Behöver granskning</option>
      </select>
    </div>
    <div><label>Import run</label>
      <select name="import_run_id">
        <option value="">Alla</option>
        <?php foreach ($runs as $run): ?>
          <option value="<?= (int) $run['id'] ?>" <?= (string) ($filters['import_run_id'] ?? '') === (string) $run['id'] ? 'selected' : '' ?>>#<?= (int) $run['id'] ?> — <?= htmlspecialchars((string) ($run['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Databrist</label>
      <select name="data_gap">
        <option value="">Alla</option>
        <?php foreach ($gapLabels as $key => $label): ?>
          <option value="<?= $key ?>" <?= (string) ($filters['data_gap'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div><label>Produktsök (för matchning)</label><input name="product_query" value="<?= htmlspecialchars((string) ($filters['product_query'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
    <div><button class="btn" type="submit">Filtrera</button></div>
  </form>

  <div class="actions-inline">
    <span class="pill">Totalt: <?= (int) ($quality['total'] ?? 0) ?></span>
    <span class="pill ok">Kopplad: <?= (int) ($quality['linked'] ?? 0) ?></span>
    <span class="pill warn">Omatchad: <?= (int) ($quality['unmatched'] ?? 0) ?></span>
    <span class="pill bad">Behöver granskning: <?= (int) ($quality['needs_review'] ?? 0) ?></span>
  </div>
</section>

<section class="card">
  <table class="table compact">
    <thead>
      <tr>
        <th>ID</th><th>Leverantör</th><th>SKU</th><th>Titel</th><th>Run</th><th>Pris</th><th>Lager</th><th>Status</th><th>Produkt</th><th>Databrister</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= (int) $row['id'] ?></td>
        <td><?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($row['supplier_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($row['supplier_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="btn" href="/admin/import-runs/<?= (int) ($row['import_run_id'] ?? 0) ?>">#<?= (int) ($row['import_run_id'] ?? 0) ?></a></td>
        <td><?= htmlspecialchars((string) ($row['price'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($row['stock_qty'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td>
          <?php $status = (string) ($row['resolved_status'] ?? 'unmatched'); ?>
          <span class="pill <?= $status === 'linked' ? 'ok' : ($status === 'needs_review' ? 'bad' : 'warn') ?>"><?= htmlspecialchars($statusLabels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td>
          <?php if ($row['product_id'] !== null): ?>
            #<?= (int) $row['product_id'] ?> — <?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          <?php else: ?>-
          <?php endif; ?>
        </td>
        <td>
          <?php foreach (($row['data_gaps'] ?? []) as $gap): ?>
            <span class="pill <?= $gap === 'missing_product_link' ? 'bad' : 'warn' ?>"><?= htmlspecialchars($gapLabels[$gap] ?? $gap, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endforeach; ?>
        </td>
        <td>
          <form method="post" action="/admin/supplier-item-review/<?= (int) $row['id'] ?>/match" style="margin-bottom:.35rem;">
            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
            <select name="product_id" required>
              <option value="">Välj produkt</option>
              <?php foreach ($products as $product): ?>
                <option value="<?= (int) $product['id'] ?>" <?= (string) ($row['product_id'] ?? '') === (string) $product['id'] ? 'selected' : '' ?>>#<?= (int) $product['id'] ?> <?= htmlspecialchars((string) $product['name'] . ' [' . ($product['sku'] ?? '-') . ']', ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn" type="submit" style="margin-top:.3rem;">Matcha / Byt</button>
          </form>

          <form method="post" action="/admin/supplier-item-review/<?= (int) $row['id'] ?>/clear" style="margin-bottom:.3rem;">
            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn" type="submit">Rensa matchning</button>
          </form>

          <form method="post" action="/admin/supplier-item-review/<?= (int) $row['id'] ?>/reviewed">
            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn" type="submit">Markera granskad</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Supplier item review | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
