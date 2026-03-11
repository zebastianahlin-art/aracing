<?php
$filters = $filters ?? ['queue' => 'all', 'query' => '', 'brand_id' => '', 'category_id' => '', 'fitment_count_band' => ''];
$totals = $totals ?? ['total_products' => 0, 'without_fitment' => 0, 'with_fitment' => 0, 'universal_products' => 0];
$statuses = $statuses ?? [];
?>
<?php ob_start(); ?>
<section class="card" style="margin-bottom:.75rem;">
  <div class="topline"><h3>Fitment arbetsvy v1</h3></div>
  <?php if (($notice ?? '') !== ''): ?><p class="pill ok"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <div class="grid-4" style="margin-bottom:.8rem;">
    <div class="card"><strong>Totalt produkter</strong><div><?= (int) $totals['total_products'] ?></div></div>
    <div class="card"><strong>Utan fitment</strong><div><?= (int) $totals['without_fitment'] ?></div></div>
    <div class="card"><strong>Med fitment</strong><div><?= (int) $totals['with_fitment'] ?></div></div>
    <div class="card"><strong>Universal-produkter</strong><div><?= (int) $totals['universal_products'] ?></div></div>
  </div>

  <form method="get" action="/admin/fitment-workflow" class="grid-4">
    <div>
      <label>Arbetskö</label>
      <select name="queue">
        <?php foreach (['all' => 'Alla', 'without_fitment' => 'Saknar fitment', 'with_fitment' => 'Har fitment', 'universal' => 'Endast universal', 'needs_review' => 'Behöver granskning'] as $value => $label): ?>
          <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['queue'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Sök produkt/SKU</label>
      <input name="query" value="<?= htmlspecialchars((string) $filters['query'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label>Brand</label>
      <select name="brand_id">
        <option value="">Alla</option>
        <?php foreach (($brands ?? []) as $brand): ?>
          <option value="<?= (int) $brand['id'] ?>" <?= $filters['brand_id'] === (string) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Kategori</label>
      <select name="category_id">
        <option value="">Alla</option>
        <?php foreach (($categories ?? []) as $category): ?>
          <option value="<?= (int) $category['id'] ?>" <?= $filters['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Fitment-volym</label>
      <select name="fitment_count_band">
        <option value="">Alla</option>
        <option value="many" <?= $filters['fitment_count_band'] === 'many' ? 'selected' : '' ?>>Många kopplingar (10+)</option>
      </select>
    </div>
    <div style="display:flex;gap:.5rem;align-items:flex-end;">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/fitment-workflow">Nollställ</a>
    </div>
  </form>
</section>

<section class="card">
  <table class="table compact">
    <thead><tr><th>Produkt</th><th>Fitment</th><th>Signal</th><th>Manuell status</th><th>Intern notering</th><th></th></tr></thead>
    <tbody>
    <?php foreach (($rows ?? []) as $row): ?>
      <tr>
        <td>
          <strong><?= htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
          <span class="muted">SKU: <?= htmlspecialchars((string) ($row['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
          <span class="muted">Brand: <?= htmlspecialchars((string) ($row['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · Kategori: <?= htmlspecialchars((string) ($row['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
          <a class="btn" href="/admin/products/<?= (int) $row['id'] ?>/edit#fitment">Öppna produkt</a>
        </td>
        <td>
          <span class="pill ok">Kopplingar: <?= (int) ($row['fitment_count'] ?? 0) ?></span><br>
          <span class="muted">Confirmed: <?= (int) ($row['confirmed_fitment_count'] ?? 0) ?> · Universal: <?= (int) ($row['universal_fitment_count'] ?? 0) ?></span>
        </td>
        <td>
          <?php $signal = $row['workflow_signal'] ?? ['label' => 'Okänt']; ?>
          <span class="pill warn"><?= htmlspecialchars((string) ($signal['label'] ?? 'Okänt'), ENT_QUOTES, 'UTF-8') ?></span>
        </td>
        <td>
          <form method="post" action="/admin/fitment-workflow/<?= (int) $row['id'] ?>/flag">
            <select name="status">
              <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($row['fitment_review_status'] ?? '') === (string) $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input name="note" value="<?= htmlspecialchars((string) ($row['fitment_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Kort intern notering">
        </td>
        <td>
            <button class="btn" type="submit">Spara</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Fitment workflow | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
