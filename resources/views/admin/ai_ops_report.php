<?php
ob_start();
$report = $report ?? [];
$sections = is_array($report['sections'] ?? null) ? $report['sections'] : [];
?>
<section class="card">
  <div class="topline">
    <h1><?= htmlspecialchars((string) ($report['title'] ?? 'AI Operational Insights / Daily Report v1'), ENT_QUOTES, 'UTF-8') ?></h1>
    <small>Genererad: <?= htmlspecialchars((string) ($report['generated_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></small>
  </div>

  <p><strong>Sammanfattning:</strong> <?= htmlspecialchars((string) ($report['summary_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
  <p><small>Detta är ett AI-assisterat beslutsstöd i v1 (förklarbar regelbaserad sammanställning), inte en automatisk beslutsmotor.</small></p>
</section>

<?php foreach ($sections as $section): ?>
  <section class="card" style="margin-top:12px;">
    <div class="topline">
      <h2 style="margin:0;"><?= htmlspecialchars((string) ($section['label'] ?? 'Sektion'), ENT_QUOTES, 'UTF-8') ?></h2>
      <span class="pill <?= ((string) ($section['status'] ?? '') === 'needs_attention') ? 'warn' : 'ok' ?>">
        <?= ((string) ($section['status'] ?? '') === 'needs_attention') ? 'Kräver uppmärksamhet' : 'Stabil' ?>
      </span>
    </div>

    <p>Aktiva signaler: <strong><?= (int) ($section['attention_count'] ?? 0) ?></strong></p>

    <table class="table compact">
      <thead>
      <tr><th>Signal</th><th>Antal</th></tr>
      </thead>
      <tbody>
      <?php foreach (($section['metrics'] ?? []) as $metric): ?>
        <tr>
          <td><?= htmlspecialchars((string) ($metric['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) ($metric['count'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="actions-inline">
      <?php foreach (($section['action_links'] ?? []) as $action): ?>
        <a class="btn" href="<?= htmlspecialchars((string) ($action['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>">
          <?= htmlspecialchars((string) ($action['label'] ?? 'Öppna'), ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
<?php
$content = (string) ob_get_clean();
$title = 'AI Operational Insights | Admin';
require __DIR__ . '/../layouts/admin.php';
