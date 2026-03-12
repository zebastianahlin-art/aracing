<?php
$filters = $filters ?? ['sort' => 'worst', 'only_missing' => '0', 'query' => ''];
$totals = $totals ?? ['categories' => 0, 'public_products' => 0, 'with_fitment' => 0, 'without_fitment' => 0];
?>
<?php ob_start(); ?>
<section class="card" style="margin-bottom:.75rem;">
  <div class="topline"><h3>Fitment coverage per kategori (v1)</h3></div>
  <p class="muted">Coverage bygger på publika produkter och om produkten har minst en confirmed/universal fitment-koppling.</p>

  <div class="grid-4" style="margin-bottom:.8rem;">
    <div class="card"><strong>Kategorier</strong><div><?= (int) $totals['categories'] ?></div></div>
    <div class="card"><strong>Publika produkter</strong><div><?= (int) $totals['public_products'] ?></div></div>
    <div class="card"><strong>Med fitment</strong><div><?= (int) $totals['with_fitment'] ?></div></div>
    <div class="card"><strong>Utan fitment</strong><div><?= (int) $totals['without_fitment'] ?></div></div>
  </div>

  <form method="get" action="/admin/fitment-coverage" class="grid-4">
    <div>
      <label>Sortering</label>
      <select name="sort">
        <option value="worst" <?= (string) $filters['sort'] === 'worst' ? 'selected' : '' ?>>Sämst coverage först</option>
        <option value="best" <?= (string) $filters['sort'] === 'best' ? 'selected' : '' ?>>Bäst coverage först</option>
      </select>
    </div>
    <div>
      <label>Visa</label>
      <select name="only_missing">
        <option value="0" <?= (string) $filters['only_missing'] === '0' ? 'selected' : '' ?>>Alla kategorier</option>
        <option value="1" <?= (string) $filters['only_missing'] === '1' ? 'selected' : '' ?>>Endast med saknad fitment</option>
      </select>
    </div>
    <div>
      <label>Kategorisök</label>
      <input name="query" value="<?= htmlspecialchars((string) $filters['query'], ENT_QUOTES, 'UTF-8') ?>" placeholder="t.ex. Bromsar">
    </div>
    <div style="display:flex;gap:.5rem;align-items:flex-end;">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/fitment-coverage">Nollställ</a>
    </div>
  </form>
</section>

<section class="card">
  <table class="table compact">
    <thead><tr><th>Kategori</th><th>Publika produkter</th><th>Med confirmed/universal</th><th>Utan fitment</th><th>Coverage</th><th>Åtgärd</th></tr></thead>
    <tbody>
    <?php foreach (($rows ?? []) as $row): ?>
      <?php $signal = $row['signal'] ?? ['code' => 'warn', 'label' => 'Okänt']; ?>
      <tr>
        <td><strong><?= htmlspecialchars((string) ($row['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
        <td><?= (int) ($row['public_product_count'] ?? 0) ?></td>
        <td><?= (int) ($row['with_fitment_count'] ?? 0) ?></td>
        <td><?= (int) ($row['without_fitment_count'] ?? 0) ?></td>
        <td>
          <span class="pill <?= ($signal['code'] ?? '') === 'bad' ? 'bad' : (($signal['code'] ?? '') === 'good' ? 'ok' : 'warn') ?>">
            <?= htmlspecialchars((string) ($signal['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </span><br>
          <span class="muted"><?= htmlspecialchars((string) ($row['coverage_ratio'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>%</span>
        </td>
        <td><a class="btn" href="<?= htmlspecialchars((string) ($row['fitment_workflow_url'] ?? '/admin/fitment-workflow'), ENT_QUOTES, 'UTF-8') ?>">Öppna fitmentkö</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (($rows ?? []) === []): ?>
      <tr><td colspan="6" class="muted">Inga kategorier matchar nuvarande filter.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Fitment coverage | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
