<?php
ob_start();
$alertsSummary = is_array($alertsSummary ?? null) ? $alertsSummary : [];
$topAlerts = is_array($alertsSummary['top_alerts'] ?? null) ? $alertsSummary['top_alerts'] : [];
?>
<section class="card">
  <h1>A-<span style="color:#e10600;">Racing</span> Admin</h1>
  <p>Katalogblocket är aktivt och leverantör/import v1 finns nu i admin för spårbar CSV-hantering.</p>
  <p>Snabbval: <a class="btn" href="/admin/ai-alerts">AI Alerts</a> <a class="btn" href="/admin/ai-ops-report">AI Ops-rapport</a> <a class="btn" href="/admin/suppliers">Leverantörer</a> <a class="btn" href="/admin/import-profiles">Importprofiler</a> <a class="btn" href="/admin/import-runs">Importkörningar</a> <a class="btn" href="/admin/purchasing">Inköpsöversikt</a></p>
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
<?php
$content = (string) ob_get_clean();
$title = 'Admin Dashboard | A-Racing';
require __DIR__ . '/../layouts/admin.php';
