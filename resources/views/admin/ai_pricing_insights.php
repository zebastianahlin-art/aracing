<?php
ob_start();
$payload = is_array($payload ?? null) ? $payload : [];
$filters = is_array($payload['filters'] ?? null) ? $payload['filters'] : [];
$rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
$counts = is_array($payload['counts'] ?? null) ? $payload['counts'] : [];
$ruleInfo = is_array($payload['rule_info'] ?? null) ? $payload['rule_info'] : [];
$typeOptions = is_array($payload['insight_type_options'] ?? null) ? $payload['insight_type_options'] : [];
$supplierOptions = is_array($payload['supplier_options'] ?? null) ? $payload['supplier_options'] : [];
$brandOptions = is_array($brandOptions ?? null) ? $brandOptions : [];
$categoryOptions = is_array($categoryOptions ?? null) ? $categoryOptions : [];
$activeDiscount = is_array($payload['active_discount'] ?? null) ? $payload['active_discount'] : null;
?>
<section class="card">
  <div class="topline">
    <h1 style="margin:0;">AI Pricing Insights / Margin pressure signals v1</h1>
    <span class="pill warn">Beslutsstöd</span>
  </div>
  <p>Operativ vy för att upptäcka marginalpress, supplierprisrörelser och prisglapp med förklarbara signaler och åtgärdslänkar.</p>
  <p><small>Ingen automatisk repricing eller automatisk publicering av prisändringar sker här.</small></p>
  <?php if ($activeDiscount !== null): ?>
    <p><small>Aktiv rabattsignal i modellen: <strong><?= htmlspecialchars((string) ($activeDiscount['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong> (<?= (float) ($activeDiscount['discount_value'] ?? 0.0) ?>%).</small></p>
  <?php endif; ?>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Filter</h2>
  <form method="get" action="/admin/ai-pricing-insights" class="grid-4">
    <div>
      <label for="insight_type">Insight-typ</label>
      <select id="insight_type" name="insight_type">
        <?php foreach ($typeOptions as $value => $label): ?>
          <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($filters['insight_type'] ?? 'all') === (string) $value) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="supplier_id">Leverantör</label>
      <select id="supplier_id" name="supplier_id">
        <option value="">Alla</option>
        <?php foreach ($supplierOptions as $supplier): ?>
          <option value="<?= (int) ($supplier['id'] ?? 0) ?>" <?= ((string) ($filters['supplier_id'] ?? '') === (string) ($supplier['id'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) ($supplier['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="brand_id">Brand</label>
      <select id="brand_id" name="brand_id">
        <option value="">Alla</option>
        <?php foreach ($brandOptions as $brand): ?>
          <option value="<?= (int) ($brand['id'] ?? 0) ?>" <?= ((string) ($filters['brand_id'] ?? '') === (string) ($brand['id'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) ($brand['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="category_id">Kategori</label>
      <select id="category_id" name="category_id">
        <option value="">Alla</option>
        <?php foreach ($categoryOptions as $category): ?>
          <option value="<?= (int) ($category['id'] ?? 0) ?>" <?= ((string) ($filters['category_id'] ?? '') === (string) ($category['id'] ?? '')) ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="linked_only">Visa</label>
      <select id="linked_only" name="linked_only">
        <option value="0" <?= ((string) ($filters['linked_only'] ?? '0') === '0') ? 'selected' : '' ?>>Alla aktiva produkter</option>
        <option value="1" <?= ((string) ($filters['linked_only'] ?? '0') === '1') ? 'selected' : '' ?>>Endast produkter med supplier-koppling</option>
      </select>
    </div>

    <div>
      <label for="search">Sök (produkt/SKU)</label>
      <input id="search" type="text" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
      <label>&nbsp;</label>
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/ai-pricing-insights">Rensa</a>
    </div>
  </form>

  <p>
    Totalt: <strong><?= (int) ($counts['total'] ?? 0) ?></strong> |
    Margin pressure: <strong><?= (int) ($counts['margin_pressure'] ?? 0) ?></strong> |
    Supplierpris ändrat: <strong><?= (int) ($counts['supplier_price_moved'] ?? 0) ?></strong> |
    Prisglapp-kontroll: <strong><?= (int) ($counts['price_gap_check'] ?? 0) ?></strong> |
    Rabattmarginalrisk: <strong><?= (int) ($counts['discount_margin_risk'] ?? 0) ?></strong>
  </p>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">Insight-lista</h2>
    <small>On-demand beräkning med enkel marginalmodell</small>
  </div>

  <?php if ($rows === []): ?>
    <p>Inga pricing insights matchade aktuella filter.</p>
  <?php else: ?>
    <table class="table compact">
      <thead>
      <tr>
        <th>Produkt</th>
        <th>Insight</th>
        <th>Nyckeltal</th>
        <th>Reason</th>
        <th>Action links</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <?php $type = (string) ($row['insight_type'] ?? ''); ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small>SKU: <?= htmlspecialchars((string) ($row['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small><br>
            <small><?= htmlspecialchars((string) ($row['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string) ($row['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small><br>
            <small>Leverantör: <?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td>
            <span class="pill <?= in_array($type, ['margin_pressure', 'price_gap_check'], true) ? 'bad' : 'warn' ?>">
              <?= htmlspecialchars((string) ($row['insight_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </span><br>
            <small><?= htmlspecialchars((string) ($row['selection_rule'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td>
            Retail: <strong><?= number_format((float) ($row['retail_price'] ?? 0.0), 2, ',', ' ') ?></strong> <?= htmlspecialchars((string) ($row['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?><br>
            Supplier: <strong><?= number_format((float) ($row['supplier_price'] ?? 0.0), 2, ',', ' ') ?></strong><br>
            Marginal: <strong><?= number_format((float) ($row['margin_amount'] ?? 0.0), 2, ',', ' ') ?></strong> (<?= number_format((float) ($row['margin_percent'] ?? 0.0), 1, ',', ' ') ?>%)<br>
            Supplier ändring: <strong><?= number_format((float) ($row['supplier_change_amount'] ?? 0.0), 2, ',', ' ') ?></strong> (<?= number_format((float) ($row['supplier_change_percent'] ?? 0.0), 1, ',', ' ') ?>%)
            <?php if (($row['discount_code'] ?? '') !== ''): ?>
              <br><small>Rabattkodsignal: <?= htmlspecialchars((string) ($row['discount_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= number_format((float) ($row['discount_percent'] ?? 0.0), 1, ',', ' ') ?>%)</small>
            <?php endif; ?>
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
  <h2 style="margin-top:0;">Regler i v1</h2>
  <ul>
    <?php foreach ($ruleInfo as $type => $rule): ?>
      <li><strong><?= htmlspecialchars((string) ($typeOptions[$type] ?? $type), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $rule, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'AI Pricing Insights | Admin';
require __DIR__ . '/../layouts/admin.php';
