<?php
ob_start();
$alerts = is_array($alerts ?? null) ? $alerts : [];
$summary = is_array($summary ?? null) ? $summary : [];
$thresholds = is_array($thresholds ?? null) ? $thresholds : [];
?>
<section class="card">
  <div class="topline">
    <h1 style="margin:0;">AI Operational Alerts / Anomaly Detection v1</h1>
    <span class="pill <?= ((int) ($summary['active_count'] ?? 0)) > 0 ? 'warn' : 'ok' ?>">
      Aktiva alerts: <?= (int) ($summary['active_count'] ?? 0) ?>
    </span>
  </div>
  <p>Regelbaserade och förklarbara operativa varningar. Detta är beslutsstöd, inte automatisk exekvering.</p>
  <p>
    <small>
      Kritiska: <?= (int) ($summary['critical_count'] ?? 0) ?>,
      varning: <?= (int) ($summary['warning_count'] ?? 0) ?>,
      info: <?= (int) ($summary['info_count'] ?? 0) ?>.
    </small>
  </p>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Aktiva alerts</h2>
  <?php if ($alerts === []): ?>
    <p>Inga aktiva operational alerts just nu.</p>
  <?php else: ?>
    <table class="table compact">
      <thead>
      <tr>
        <th>Severity</th>
        <th>Alert</th>
        <th>Count</th>
        <th>Förklaring</th>
        <th>Åtgärd</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($alerts as $alert): ?>
        <?php $severity = (string) ($alert['severity'] ?? 'info'); ?>
        <tr>
          <td>
            <span class="pill <?= $severity === 'critical' ? 'bad' : ($severity === 'warning' ? 'warn' : 'ok') ?>">
              <?= htmlspecialchars($severity, ENT_QUOTES, 'UTF-8') ?>
            </span>
          </td>
          <td>
            <strong><?= htmlspecialchars((string) ($alert['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br>
            <small><?= htmlspecialchars((string) ($alert['alert_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td><strong><?= (int) ($alert['count'] ?? 0) ?></strong></td>
          <td>
            <?= htmlspecialchars((string) ($alert['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
            <small><?= htmlspecialchars((string) ($alert['explanation'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td>
            <a class="btn" href="<?= htmlspecialchars((string) ($alert['target_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars((string) ($alert['action_label'] ?? 'Öppna'), ENT_QUOTES, 'UTF-8') ?>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<section class="card" style="margin-top:12px;">
  <h2 style="margin-top:0;">Regler och trösklar (v1)</h2>
  <table class="table compact">
    <thead><tr><th>Alert-typ</th><th>Warning</th><th>Critical</th></tr></thead>
    <tbody>
    <?php foreach ($thresholds as $type => $rule): ?>
      <tr>
        <td><?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int) ($rule['warning'] ?? 0) ?></td>
        <td><?= (int) ($rule['critical'] ?? 0) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'AI Operational Alerts | Admin';
require __DIR__ . '/../layouts/admin.php';
