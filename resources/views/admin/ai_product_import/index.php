<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $filters */
/** @var array<int,string> $status_options */
/** @var array<int,string> $quality_options */

$qualityLabels = [
    'high' => 'Hög kvalitet',
    'medium' => 'Medel',
    'low' => 'Låg',
];
?>
<div class="topline">
  <h1>AI URL-import v1</h1>
</div>

<?php if (!empty($message)): ?>
  <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <div class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:1rem;">
  <h2>Importera en produkt-URL</h2>
  <p>Klistra in en URL för att skapa ett granskningsbart utkast. Ingen autopublicering sker i v1.</p>
  <form method="post" action="/admin/ai-product-import/import">
    <label for="source_url">Produkt-URL</label>
    <input id="source_url" name="source_url" type="url" required placeholder="https://...">
    <div style="margin-top:.6rem;">
      <button class="btn" type="submit">Starta URL-import</button>
    </div>
  </form>
</div>

<div class="card">
  <h2>Utkast</h2>
  <form method="get" action="/admin/ai-product-import" class="grid" style="grid-template-columns:220px 220px 160px; margin-bottom:.8rem;">
    <div>
      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="">Alla</option>
        <?php foreach ($status_options as $status): ?>
          <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="quality_label">Kvalitet</label>
      <select id="quality_label" name="quality_label">
        <option value="">Alla</option>
        <?php foreach ($quality_options as $quality): ?>
          <option value="<?= htmlspecialchars($quality, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['quality_label'] ?? '') === $quality ? 'selected' : '' ?>><?= htmlspecialchars($qualityLabels[$quality] ?? $quality, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <button class="btn" type="submit">Filtrera</button>
    </div>
  </form>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Källa</th>
        <th>Status</th>
        <th>Kvalitet</th>
        <th>Strategi</th>
        <th>Titel</th>
        <th>Brand</th>
        <th>SKU</th>
        <th>Skapad</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <?php $quality = (string) ($row['quality_label'] ?? ''); ?>
        <tr>
          <td><a href="/admin/ai-product-import/<?= (int) $row['id'] ?>">#<?= (int) $row['id'] ?></a></td>
          <td>
            <?= htmlspecialchars((string) ($row['source_domain'] ?: $row['source_url']), ENT_QUOTES, 'UTF-8') ?><br>
            <small><?= htmlspecialchars((string) $row['source_url'], ENT_QUOTES, 'UTF-8') ?></small>
          </td>
          <td><span class="pill"><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td>
            <?php if ($quality !== ''): ?>
              <span class="pill"><?= htmlspecialchars((string) ($qualityLabels[$quality] ?? $quality), ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
              <span>-</span>
            <?php endif; ?>
          </td>
          <td>
            <?= htmlspecialchars((string) ($row['extraction_strategy'] ?? 'generic_ai'), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($row['parser_key'])): ?><br><small><?= htmlspecialchars((string) $row['parser_key'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string) ($row['import_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['import_brand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['import_sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($rows === []): ?>
        <tr><td colspan="9">Inga utkast hittades för valt filter.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
