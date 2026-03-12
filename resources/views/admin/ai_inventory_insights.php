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
?>
<section class="card">
  <div class="topline">
    <h1 style="margin:0;">AI Inventory Insights / Slow movers & stock risk v1</h1>
    <span class="pill warn">Beslutsstöd</span>
  </div>
  <p>Regelbaserad inventory insight-vy för att upptäcka slow movers, stockout-risk och lageravvikelser med tydliga åtgärdslänkar.</p>
  <p><small>V1 är avsiktligt enkel och förklarbar. Ingen automatisk lagerstyrning, automatisk inköpsorder eller repricing sker här.</small></p>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Filter</h2>
  <form method="get" action="/admin/ai-inventory-insights" class="grid-4">
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
      <label for="search">Sök (produktnamn/SKU)</label>
      <input id="search" type="text" name="search" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
      <label>&nbsp;</label>
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/ai-inventory-insights">Rensa</a>
    </div>
  </form>

  <p>
    Totalt insights: <strong><?= (int) ($counts['total'] ?? 0) ?></strong> |
    Slow movers: <strong><?= (int) ($counts['slow_mover'] ?? 0) ?></strong> |
    Stockout-risk: <strong><?= (int) ($counts['stockout_risk'] ?? 0) ?></strong> |
    Högt lager/låg rörelse: <strong><?= (int) ($counts['high_stock_low_velocity'] ?? 0) ?></strong> |
    Efterfrågan utan lager: <strong><?= (int) ($counts['demand_without_stock'] ?? 0) ?></strong>
  </p>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">Insight-lista</h2>
    <small>On-demand beräkning (ingen tung historikmotor i v1)</small>
  </div>

  <?php if ($rows === []): ?>
    <p>Inga inventory insights matchade aktuella filter.</p>
  <?php else: ?>
    <table class="table compact">
      <thead>
      <tr>
        <th>Produkt</th>
        <th>Insight type</th>
        <th>Nyckeltal</th>
        <th>Sammanfattning</th>
        <th>Åtgärdslänkar</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td>
            <strong><?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small>SKU: <?= htmlspecialchars((string) ($row['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small><br>
            <small>
              <?= htmlspecialchars((string) ($row['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> /
              <?= htmlspecialchars((string) ($row['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            </small><br>
            <small>Leverantör: <?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td>
            <span class="pill <?= in_array((string) ($row['insight_type'] ?? ''), ['stockout_risk', 'demand_without_stock'], true) ? 'bad' : 'warn' ?>">
              <?= htmlspecialchars((string) ($row['insight_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </span>
          </td>
          <td>
            Lager: <strong><?= (int) ($row['stock_quantity'] ?? 0) ?></strong><br>
            Sålt 30/60 dagar: <strong><?= (int) ($row['sold_last_30_days'] ?? 0) ?> / <?= (int) ($row['sold_last_60_days'] ?? 0) ?></strong><br>
            Aktiva stock alerts: <strong><?= (int) ($row['active_stock_alerts'] ?? 0) ?></strong><br>
            Pending restock: <strong><?= (int) ($row['pending_restock_qty'] ?? 0) ?></strong>
            <?php if ((string) ($row['restock_status'] ?? '') !== ''): ?>
              <br><small>Restockstatus: <?= htmlspecialchars((string) ($row['restock_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
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
  <h2 style="margin-top:0;">Regler i v1 (förklarbara)</h2>
  <ul>
    <?php foreach ($ruleInfo as $type => $rule): ?>
      <li><strong><?= htmlspecialchars((string) ($typeOptions[$type] ?? $type), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars((string) $rule, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'AI Inventory Insights | Admin';
require __DIR__ . '/../layouts/admin.php';
