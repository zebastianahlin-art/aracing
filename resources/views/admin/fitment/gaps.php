<?php
$filters = $filters ?? ['reason' => 'all', 'query' => '', 'brand_id' => '', 'category_id' => ''];
$totals = $totals ?? ['rows' => 0, 'no_fitment_links' => 0, 'pending_supplier_candidates' => 0, 'needs_review_flag' => 0];
?>
<?php ob_start(); ?>
<section class="card" style="margin-bottom:.75rem;">
  <div class="topline"><h3>Fitment gap-kö v1</h3></div>
  <p class="muted">Operativ arbetslista för produkter med fitment-gap. En produkt kan ha flera orsaker samtidigt.</p>

  <div class="grid-4" style="margin-bottom:.8rem;">
    <div class="card"><strong>Produkter i kö</strong><div><?= (int) ($totals['rows'] ?? 0) ?></div></div>
    <div class="card"><strong>Saknar fitment</strong><div><?= (int) ($totals['no_fitment_links'] ?? 0) ?></div></div>
    <div class="card"><strong>Pending supplier-underlag</strong><div><?= (int) ($totals['pending_supplier_candidates'] ?? 0) ?></div></div>
    <div class="card"><strong>Needs review-flagg</strong><div><?= (int) ($totals['needs_review_flag'] ?? 0) ?></div></div>
  </div>

  <form method="get" action="/admin/fitment-gaps" class="grid-4">
    <div>
      <label>Gap-signal</label>
      <select name="reason">
        <?php foreach ([
          'all' => 'Alla gap-signaler',
          'no_fitment_links' => 'Inga fitments',
          'universal_only' => 'Endast universal',
          'pending_supplier_candidates' => 'Pending supplier candidates',
          'needs_review_flag' => 'Needs review',
          'category_low_coverage' => 'Kategori med låg coverage',
        ] as $value => $label): ?>
          <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['reason'] ?? 'all') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Sök produkt/SKU</label>
      <input name="query" value="<?= htmlspecialchars((string) ($filters['query'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label>Brand</label>
      <select name="brand_id">
        <option value="">Alla</option>
        <?php foreach (($brands ?? []) as $brand): ?>
          <option value="<?= (int) $brand['id'] ?>" <?= (string) ($filters['brand_id'] ?? '') === (string) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Kategori</label>
      <select name="category_id">
        <option value="">Alla</option>
        <?php foreach (($categories ?? []) as $category): ?>
          <option value="<?= (int) $category['id'] ?>" <?= (string) ($filters['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:.5rem;align-items:flex-end;">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/fitment-gaps">Nollställ</a>
    </div>
  </form>
</section>

<section class="card">
  <table class="table compact">
    <thead><tr><th>Produkt</th><th>Fitmentstatus</th><th>Gap-signaler</th><th>Åtgärd</th></tr></thead>
    <tbody>
    <?php foreach (($rows ?? []) as $row): ?>
      <tr>
        <td>
          <strong><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
          <span class="muted">SKU: <?= htmlspecialchars((string) (($row['sku'] ?? '') !== '' ? $row['sku'] : '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">Brand: <?= htmlspecialchars((string) (($row['brand_name'] ?? '') !== '' ? $row['brand_name'] : '-'), ENT_QUOTES, 'UTF-8') ?> · Kategori: <?= htmlspecialchars((string) (($row['category_name'] ?? '') !== '' ? $row['category_name'] : '-'), ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td>
          <span class="pill ok">Totalt: <?= (int) ($row['fitment_count'] ?? 0) ?></span><br>
          <span class="muted">Confirmed: <?= (int) ($row['confirmed_fitment_count'] ?? 0) ?> · Universal: <?= (int) ($row['universal_fitment_count'] ?? 0) ?></span><br>
          <span class="muted">Pending supplier: <?= (int) ($row['pending_supplier_candidates_count'] ?? 0) ?></span>
          <?php if (is_array($row['category_coverage'] ?? null)): ?>
            <br><span class="pill warn"><?= htmlspecialchars((string) (($row['category_coverage']['label'] ?? 'Låg coverage')), ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?php foreach (($row['gap_reasons'] ?? []) as $reason): ?>
            <div style="margin-bottom:.25rem;">
              <span class="pill warn"><?= htmlspecialchars((string) ($reason['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
              <div class="muted" style="margin-top:.15rem;"><?= htmlspecialchars((string) ($reason['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          <?php endforeach; ?>
        </td>
        <td>
          <?php $actions = $row['actions'] ?? []; ?>
          <a class="btn" href="<?= htmlspecialchars((string) ($actions['product_url'] ?? '/admin/products/' . (int) ($row['id'] ?? 0) . '/edit#fitment'), ENT_QUOTES, 'UTF-8') ?>">Öppna produkt</a><br><br>
          <a class="btn" href="/admin/fitment-workflow">Fitment workflow</a><br><br>
          <a class="btn" href="/admin/supplier-fitment-review?status=pending">Supplier review</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Fitment gap-kö | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
