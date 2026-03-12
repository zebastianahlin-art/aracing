<?php
ob_start();
$payload = is_array($payload ?? null) ? $payload : [];
$filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
$rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
$counts = is_array($payload['counts'] ?? null) ? $payload['counts'] : [];
$ruleInfo = is_array($payload['rule_info'] ?? null) ? $payload['rule_info'] : [];
$gapTypeOptions = is_array($payload['gap_type_options'] ?? null) ? $payload['gap_type_options'] : [];
$supplierOptions = is_array($payload['supplier_options'] ?? null) ? $payload['supplier_options'] : [];
$brandOptions = is_array($payload['brand_options'] ?? null) ? $payload['brand_options'] : [];
$categoryOptions = is_array($payload['category_options'] ?? null) ? $payload['category_options'] : [];
?>
<section class="card">
  <div class="topline">
    <h1 style="margin:0;">AI Assortment Gap Insights v1</h1>
    <span class="pill warn">Beslutsstöd / ingen auto-åtgärd</span>
  </div>
  <p>Operativ vy för sortimentsluckor baserat på search, supplier, fitment, watchlist och enkel efterfrågesignal.</p>
  <p>
    Totalt: <strong><?= (int) ($counts['total'] ?? 0) ?></strong>
    | Search: <strong><?= (int) ($counts['search_gap'] ?? 0) ?></strong>
    | Supplier: <strong><?= (int) ($counts['supplier_gap'] ?? 0) ?></strong>
    | Fitment: <strong><?= (int) ($counts['fitment_gap'] ?? 0) ?></strong>
    | Watchlist: <strong><?= (int) ($counts['watchlist_gap'] ?? 0) ?></strong>
    | Demand: <strong><?= (int) ($counts['demand_gap'] ?? 0) ?></strong>
  </p>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Filter</h2>
  <form method="get" class="grid-4">
    <div>
      <label for="gap_type">Gap-typ</label>
      <select id="gap_type" name="gap_type">
        <?php foreach ($gapTypeOptions as $value => $label): ?>
          <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['gap_type'] ?? 'all') === (string) $value) ? 'selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="supplier_id">Supplier</label>
      <select id="supplier_id" name="supplier_id">
        <option value="">Alla</option>
        <?php foreach ($supplierOptions as $supplier): ?>
          <?php $selected = (string) ($filters['supplier_id'] ?? '') === (string) ($supplier['id'] ?? ''); ?>
          <option value="<?= (int) ($supplier['id'] ?? 0) ?>" <?= $selected ? 'selected' : '' ?>><?= htmlspecialchars((string) ($supplier['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="brand_id">Brand</label>
      <select id="brand_id" name="brand_id">
        <option value="">Alla</option>
        <?php foreach ($brandOptions as $brand): ?>
          <?php $selected = (string) ($filters['brand_id'] ?? '') === (string) ($brand['id'] ?? ''); ?>
          <option value="<?= (int) ($brand['id'] ?? 0) ?>" <?= $selected ? 'selected' : '' ?>><?= htmlspecialchars((string) ($brand['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="category_id">Kategori</label>
      <select id="category_id" name="category_id">
        <option value="">Alla</option>
        <?php foreach ($categoryOptions as $category): ?>
          <?php $selected = (string) ($filters['category_id'] ?? '') === (string) ($category['id'] ?? ''); ?>
          <option value="<?= (int) ($category['id'] ?? 0) ?>" <?= $selected ? 'selected' : '' ?>><?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label><input type="checkbox" name="watched_only" value="1" <?= ((string) ($filters['watched_only'] ?? '0') === '1') ? 'checked' : '' ?>> Endast watchlist</label>
    </div>
    <div><button class="btn" type="submit">Filtrera</button></div>
  </form>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Insights</h2>
  <?php if ($rows === []): ?>
    <p>Inga assortment gaps hittades med nuvarande filter.</p>
  <?php else: ?>
    <table class="table compact">
      <thead>
      <tr>
        <th>Gap type</th>
        <th>Kontext</th>
        <th>Reason</th>
        <th>Nyckelsignaler</th>
        <th>Kombinerad signal</th>
        <th>Action links</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <span class="pill warn"><?= htmlspecialchars((string) ($row['gap_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span><br>
            <small>Prioritet: <?= (int) ($row['priority'] ?? 0) ?></small>
          </td>
          <td>
            <strong><?= htmlspecialchars((string) ($row['context'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small><?= htmlspecialchars((string) ($row['context_meta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td><?= htmlspecialchars((string) ($row['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td>
            <?php foreach ((array) ($row['signals'] ?? []) as $label => $value): ?>
              <strong><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?><br>
            <?php endforeach; ?>
          </td>
          <td><small><?= htmlspecialchars((string) ($row['combined_signal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small></td>
          <td>
            <?php foreach ((array) ($row['action_links'] ?? []) as $link): ?>
              <a class="btn" style="margin:0 0 6px 0;display:inline-block;" href="<?= htmlspecialchars((string) ($link['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) ($link['label'] ?? 'Öppna'), ENT_QUOTES, 'UTF-8') ?>
              </a><br>
            <?php endforeach; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Regler i v1</h2>
  <ul>
    <?php foreach ($ruleInfo as $type => $rule): ?>
      <li><strong><?= htmlspecialchars((string) ($gapTypeOptions[$type] ?? $type), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $rule, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'AI Assortment Gap Insights | Admin';
require __DIR__ . '/../layouts/admin.php';
