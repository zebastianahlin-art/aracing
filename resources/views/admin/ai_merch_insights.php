<?php
ob_start();
$payload = is_array($payload ?? null) ? $payload : [];
$filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
$rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
$counts = is_array($payload['counts'] ?? null) ? $payload['counts'] : [];
$typeOptions = is_array($payload['insight_type_options'] ?? null) ? $payload['insight_type_options'] : [];
$ruleInfo = is_array($payload['rule_info'] ?? null) ? $payload['rule_info'] : [];
?>
<section class="card">
  <div class="topline">
    <h1 style="margin:0;">AI merchandising optimization / section performance insights (v1)</h1>
    <small>Beslutsstöd: inga automatiska section-ändringar eller autopublicering utförs.</small>
  </div>

  <form method="get" action="/admin/ai-merch-insights" class="grid" style="margin-top:12px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
    <div>
      <label for="insight_type">Insight type</label>
      <select id="insight_type" name="insight_type">
        <?php foreach ($typeOptions as $value => $label): ?>
          <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['insight_type'] ?? '') === (string) $value) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label>&nbsp;</label>
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/ai-merch-insights">Rensa</a>
    </div>
  </form>

  <p>
    Totalt insights: <strong><?= (int) ($counts['total'] ?? 0) ?></strong> |
    Weak stock: <strong><?= (int) ($counts['weak_section_stock'] ?? 0) ?></strong> |
    Stale: <strong><?= (int) ($counts['stale_section'] ?? 0) ?></strong> |
    Low signal: <strong><?= (int) ($counts['low_signal_section'] ?? 0) ?></strong> |
    Promising: <strong><?= (int) ($counts['promising_section'] ?? 0) ?></strong>
  </p>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">Section insights</h2>
    <small>On-demand och förklarbar regelmodell. Ingen tung campaign analytics-motor i v1.</small>
  </div>

  <?php if ($rows === []): ?>
    <p>Inga merchandising-insights matchade aktuella filter.</p>
  <?php else: ?>
    <table class="table compact">
      <thead>
      <tr>
        <th>Section</th>
        <th>Insight type</th>
        <th>Product count</th>
        <th>Available / buyable</th>
        <th>Demand / order signal</th>
        <th>Reason</th>
        <th>Actions</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <?php $type = (string) ($row['insight_type'] ?? ''); ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) ($row['section_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small>Key: <?= htmlspecialchars((string) ($row['section_key'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td>
            <span class="pill <?= in_array($type, ['weak_section_stock', 'low_signal_section'], true) ? 'bad' : ($type === 'stale_section' ? 'warn' : 'ok') ?>">
              <?= htmlspecialchars((string) ($row['insight_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </span>
          </td>
          <td><?= (int) ($row['product_count'] ?? 0) ?></td>
          <td>
            <strong><?= (int) ($row['buyable_count'] ?? 0) ?></strong> / <?= (int) ($row['product_count'] ?? 0) ?><br>
            <small>Fresh: <?= (int) ($row['fresh_product_count'] ?? 0) ?></small>
          </td>
          <td>
            Score: <strong><?= (int) ($row['demand_score'] ?? 0) ?></strong><br>
            <small>
              Sold 30/60: <?= (int) ($row['sold_last_30_days'] ?? 0) ?> / <?= (int) ($row['sold_last_60_days'] ?? 0) ?>,
              wishlist: <?= (int) ($row['wishlist_count'] ?? 0) ?>,
              alerts: <?= (int) ($row['active_stock_alerts'] ?? 0) ?>
            </small>
          </td>
          <td>
            <strong><?= htmlspecialchars((string) ($row['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small><?= htmlspecialchars((string) ($row['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td>
            <?php foreach (($row['action_links'] ?? []) as $link): ?>
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
  <h2 style="margin-top:0;">Regler i v1 (enkla och justerbara)</h2>
  <ul>
    <?php foreach ($ruleInfo as $type => $rule): ?>
      <li><strong><?= htmlspecialchars((string) ($typeOptions[$type] ?? $type), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $rule, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'AI Merch Insights | Admin';
require __DIR__ . '/../layouts/admin.php';
