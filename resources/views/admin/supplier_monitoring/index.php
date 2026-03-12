<?php declare(strict_types=1);
/** @var array<int, array<string, mixed>> $rows */
/** @var array<string, mixed> $filters */
/** @var array<string, int> $counts */
/** @var array<int, array<string, mixed>> $suppliers */

$typeLabels = [
    'price_increase' => 'Prisökning',
    'price_decrease' => 'Prissänkning',
    'availability_lost' => 'Tillgänglighet förlorad',
    'availability_restored' => 'Tillgänglighet åter',
    'stock_dropped' => 'Lager minskat',
    'stock_restored' => 'Lager återfyllt',
    'missing_in_recent_import' => 'Saknas i senaste import',
    'newly_seen_item' => 'Ny i senaste import',
];

$scopeLabels = [
    'price' => 'Prisändringar',
    'stock' => 'Lager / availability',
    'assortment' => 'Sortimentsavvikelser',
];

ob_start();
?>
<section class="card">
  <div class="topline"><h1>Supplier monitoring v1</h1></div>

  <form method="get" class="grid-4" style="margin-bottom:.9rem;">
    <div><label>Leverantör</label>
      <select name="supplier_id">
        <option value="">Alla</option>
        <?php foreach ($suppliers as $supplier): ?>
          <option value="<?= (int) $supplier['id'] ?>" <?= (string) ($filters['supplier_id'] ?? '') === (string) $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div><label>Avvikelsetyp</label>
      <select name="deviation_type">
        <option value="">Alla</option>
        <?php foreach ($typeLabels as $key => $label): ?>
          <option value="<?= $key ?>" <?= (string) ($filters['deviation_type'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div><label>Snabbfilter</label>
      <select name="deviation_scope">
        <option value="">Alla</option>
        <?php foreach ($scopeLabels as $key => $label): ?>
          <option value="<?= $key ?>" <?= (string) ($filters['deviation_scope'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label><input type="checkbox" name="linked_only" value="1" <?= (bool) ($filters['linked_only'] ?? false) ? 'checked' : '' ?> style="width:auto; margin-right:.4rem;"> Endast kopplade produkter</label>
      <button class="btn" type="submit">Filtrera</button>
    </div>
  </form>

  <div class="actions-inline">
    <span class="pill">Totalt: <?= (int) ($counts['total'] ?? 0) ?></span>
    <span class="pill">Pris: <?= (int) ($counts['price'] ?? 0) ?></span>
    <span class="pill">Lager: <?= (int) ($counts['stock'] ?? 0) ?></span>
    <span class="pill">Sortiment: <?= (int) ($counts['assortment'] ?? 0) ?></span>
  </div>
</section>

<section class="card" style="margin-top:.7rem;">
  <table class="table compact">
    <thead>
      <tr>
        <th>Leverantör</th><th>Artikel</th><th>Avvikelse</th><th>Tidigare</th><th>Nytt</th><th>Förändring</th><th>Tidpunkt</th><th>Action links</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <strong><?= htmlspecialchars((string) ($row['supplier_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <?= htmlspecialchars((string) ($row['supplier_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            <?php if (($row['product_id'] ?? null) !== null): ?>
              <br><small>Produkt #<?= (int) $row['product_id'] ?> <?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
            <?php endif; ?>
          </td>
          <td><span class="pill"><?= htmlspecialchars($typeLabels[(string) ($row['type'] ?? '')] ?? (string) ($row['type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars((string) ($row['previous_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['new_value'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) (($row['change_pct'] ?? null) ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['detected_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php foreach (($row['actions'] ?? []) as $action): ?>
              <a class="btn" href="<?= htmlspecialchars((string) ($action['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" style="display:inline-block;margin:.1rem 0;">
                <?= htmlspecialchars((string) ($action['label'] ?? 'Öppna'), ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Supplier monitoring | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
