<?php
ob_start();
$alertsSummary = is_array($alertsSummary ?? null) ? $alertsSummary : [];
$topAlerts = is_array($alertsSummary['top_alerts'] ?? null) ? $alertsSummary['top_alerts'] : [];
$inventoryInsightCounts = is_array($inventoryInsightCounts ?? null) ? $inventoryInsightCounts : [];
$pricingInsightCounts = is_array($pricingInsightCounts ?? null) ? $pricingInsightCounts : [];
$assortmentGapCounts = is_array($assortmentGapCounts ?? null) ? $assortmentGapCounts : [];
$demandSignalCounts = is_array($demandSignalCounts ?? null) ? $demandSignalCounts : [];
$merchInsightCounts = is_array($merchInsightCounts ?? null) ? $merchInsightCounts : [];
?>
<section class="card">
  <h1>A-<span style="color:#e10600;">Racing</span> Admin</h1>
  <p>Katalogblocket är aktivt och leverantör/import v1 finns nu i admin för spårbar CSV-hantering.</p>
  <p>Snabbval: <a class="btn" href="/admin/ai-alerts">AI Alerts</a> <a class="btn" href="/admin/ai-ops-report">AI Ops-rapport</a> <a class="btn" href="/admin/ai-demand-signals">AI Demand Signals</a> <a class="btn" href="/admin/ai-merch-insights">AI Merch Insights</a> <a class="btn" href="/admin/suppliers">Leverantörer</a> <a class="btn" href="/admin/supplier-watchlist">Supplier watchlist</a> <a class="btn" href="/admin/import-profiles">Importprofiler</a> <a class="btn" href="/admin/import-runs">Importkörningar</a> <a class="btn" href="/admin/purchasing">Inköpsöversikt</a></p>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">Operational alerts (översikt)</h2>
    <a class="btn" href="/admin/ai-alerts">Öppna AI Alerts</a>
  </div>
  <p>
    Aktiva alerts: <strong><?= (int) ($alertsSummary['active_count'] ?? 0) ?></strong>
    (kritiska: <?= (int) ($alertsSummary['critical_count'] ?? 0) ?>,
    varning: <?= (int) ($alertsSummary['warning_count'] ?? 0) ?>,
    info: <?= (int) ($alertsSummary['info_count'] ?? 0) ?>).
  </p>

  <?php if ($topAlerts !== []): ?>
    <table class="table compact">
      <thead>
      <tr><th>Severity</th><th>Alert</th><th>Count</th><th>Åtgärd</th></tr>
      </thead>
      <tbody>
      <?php foreach ($topAlerts as $alert): ?>
        <?php $severity = (string) ($alert['severity'] ?? 'info'); ?>
        <tr>
          <td>
            <span class="pill <?= $severity === 'critical' ? 'bad' : ($severity === 'warning' ? 'warn' : 'ok') ?>">
              <?= htmlspecialchars($severity, ENT_QUOTES, 'UTF-8') ?>
            </span>
          </td>
          <td><?= htmlspecialchars((string) ($alert['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) ($alert['count'] ?? 0) ?></td>
          <td><a class="btn" href="<?= htmlspecialchars((string) ($alert['target_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">Öppna</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p><small>Inga aktiva operativa alerts just nu.</small></p>
  <?php endif; ?>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">AI Inventory Insights (v1)</h2>
    <a class="btn" href="/admin/ai-inventory-insights">Öppna inventory insights</a>
  </div>
  <p>Totalt: <strong><?= (int) ($inventoryInsightCounts['total'] ?? 0) ?></strong> | Slow movers: <strong><?= (int) ($inventoryInsightCounts['slow_mover'] ?? 0) ?></strong> | Stockout-risk: <strong><?= (int) ($inventoryInsightCounts['stockout_risk'] ?? 0) ?></strong></p>
  <p><small>Signalen är regelbaserad och reviewbar. Ingen automatisk inköpsorder eller automatisk lagerstyrning utförs.</small></p>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">AI Pricing Insights (v1)</h2>
    <a class="btn" href="/admin/ai-pricing-insights">Öppna pricing insights</a>
  </div>
  <p>Totalt: <strong><?= (int) ($pricingInsightCounts['total'] ?? 0) ?></strong> | Margin pressure: <strong><?= (int) ($pricingInsightCounts['margin_pressure'] ?? 0) ?></strong> | Supplierpris ändrat: <strong><?= (int) ($pricingInsightCounts['supplier_price_moved'] ?? 0) ?></strong></p>
  <p><small>Signalen är regelbaserad och förklarbar. Ingen automatisk repricing eller automatisk prispublicering utförs.</small></p>
</section>



<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">AI Demand Signals (v1)</h2>
    <a class="btn" href="/admin/ai-demand-signals">Öppna demand signals</a>
  </div>
  <p>Totalt: <strong><?= (int) ($demandSignalCounts['total'] ?? 0) ?></strong> | High interest/low conversion: <strong><?= (int) ($demandSignalCounts['high_interest_low_conversion'] ?? 0) ?></strong> | Repeated interest/no stock: <strong><?= (int) ($demandSignalCounts['repeated_interest_no_stock'] ?? 0) ?></strong></p>
  <p><small>Signalen är regelbaserad och förklarbar. Detta är beslutsstöd, inte automatisk exekvering av pris, lager eller merchandising.</small></p>
</section>


<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">AI Merch Insights (v1)</h2>
    <a class="btn" href="/admin/ai-merch-insights">Öppna merch insights</a>
  </div>
  <p>Totalt: <strong><?= (int) ($merchInsightCounts['total'] ?? 0) ?></strong> | Weak stock: <strong><?= (int) ($merchInsightCounts['weak_section_stock'] ?? 0) ?></strong> | Stale: <strong><?= (int) ($merchInsightCounts['stale_section'] ?? 0) ?></strong> | Low signal: <strong><?= (int) ($merchInsightCounts['low_signal_section'] ?? 0) ?></strong> | Promising: <strong><?= (int) ($merchInsightCounts['promising_section'] ?? 0) ?></strong></p>
  <p><small>Signalerna är förklarbara och review-first. Ingen automatisk section-publicering eller automatisk optimering utförs.</small></p>
</section>

<section class="card" style="margin-top:12px;">
  <div class="topline">
    <h2 style="margin:0;">AI Assortment Gap Insights (v1)</h2>
    <a class="btn" href="/admin/ai-assortment-gaps">Öppna assortment gaps</a>
  </div>
  <p>Totalt: <strong><?= (int) ($assortmentGapCounts['total'] ?? 0) ?></strong> | Search: <strong><?= (int) ($assortmentGapCounts['search_gap'] ?? 0) ?></strong> | Supplier: <strong><?= (int) ($assortmentGapCounts['supplier_gap'] ?? 0) ?></strong> | Fitment: <strong><?= (int) ($assortmentGapCounts['fitment_gap'] ?? 0) ?></strong> | Watchlist: <strong><?= (int) ($assortmentGapCounts['watchlist_gap'] ?? 0) ?></strong> | Demand: <strong><?= (int) ($assortmentGapCounts['demand_gap'] ?? 0) ?></strong></p>
  <p><small>Signalen är regelbaserad och förklarbar. Inga automatiska inköpsordrar, importer eller autopublicering sker.</small></p>
</section>

<?php
$content = (string) ob_get_clean();
$title = 'Admin Dashboard | A-Racing';
require __DIR__ . '/../layouts/admin.php';
