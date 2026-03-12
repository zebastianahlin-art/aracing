<?php declare(strict_types=1);
/** @var array<int,array<string,mixed>> $entries */
/** @var array<int,array<string,mixed>> $suppliers */
/** @var array<int,array<string,mixed>> $brands */
/** @var array<string,int> $counts */

$entries = is_array($entries ?? null) ? $entries : [];
$suppliers = is_array($suppliers ?? null) ? $suppliers : [];
$brands = is_array($brands ?? null) ? $brands : [];
$counts = is_array($counts ?? null) ? $counts : ['supplier' => 0, 'brand' => 0, 'total' => 0];
$message = trim((string) ($message ?? ''));
$error = trim((string) ($error ?? ''));

ob_start();
?>
<section class="card">
  <div class="topline">
    <h1 style="margin:0;">Supplier watchlist v1</h1>
    <span class="pill warn">Aktiva: <?= (int) ($counts['total'] ?? 0) ?></span>
  </div>
  <p>Markera leverantörer och brands som särskilt bevakade. Detta är prioriteringssignal till supplier monitoring och operational alerts.</p>
  <p><small>Aktiva suppliers: <?= (int) ($counts['supplier'] ?? 0) ?> · Aktiva brands: <?= (int) ($counts['brand'] ?? 0) ?></small></p>

  <?php if ($message !== ''): ?><p class="pill ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <?php if ($error !== ''): ?><p class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
</section>

<section class="card" style="margin-top:.8rem;">
  <h2 style="margin-top:0;">Lägg till bevakning</h2>
  <form method="post" action="/admin/supplier-watchlist" class="grid-4">
    <div>
      <label>Typ</label>
      <select name="entity_type" required>
        <option value="supplier">Supplier</option>
        <option value="brand">Brand</option>
      </select>
    </div>
    <div>
      <label>Supplier</label>
      <select name="supplier_id">
        <option value="">Välj supplier</option>
        <?php foreach ($suppliers as $supplier): ?>
          <option value="<?= (int) $supplier['id'] ?>"><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?> (#<?= (int) $supplier['id'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <small>Fyll när typ = supplier.</small>
    </div>
    <div>
      <label>Brand</label>
      <select name="brand_id">
        <option value="">Välj brand</option>
        <?php foreach ($brands as $brand): ?>
          <option value="<?= (int) $brand['id'] ?>"><?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?> (#<?= (int) $brand['id'] ?>)</option>
        <?php endforeach; ?>
      </select>
      <small>Fyll när typ = brand.</small>
    </div>
    <div>
      <label>Prioritet</label>
      <select name="priority_level" required>
        <option value="normal">normal</option>
        <option value="high">high</option>
        <option value="critical">critical</option>
      </select>
    </div>
    <div>
      <label>Intern anteckning</label>
      <input type="text" name="note" maxlength="2000" placeholder="Varför objektet bevakas">
    </div>
    <div><button class="btn" type="submit">Spara bevakning</button></div>
  </form>
</section>

<section class="card" style="margin-top:.8rem;">
  <h2 style="margin-top:0;">Bevakade objekt</h2>
  <table class="table compact">
    <thead>
      <tr><th>Typ</th><th>Objekt</th><th>Prioritet</th><th>Note</th><th>Aktiv</th><th>Uppdatera</th></tr>
    </thead>
    <tbody>
    <?php foreach ($entries as $entry): ?>
      <?php $formId = 'watchlist-row-' . (int) ($entry['id'] ?? 0); ?>
      <tr>
        <td><?= htmlspecialchars((string) ($entry['entity_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($entry['entity_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <small>#<?= (int) ($entry['entity_id'] ?? 0) ?></small></td>
        <td>
          <?php $p = (string) ($entry['priority_level'] ?? 'normal'); ?>
          <select name="priority_level" form="<?= $formId ?>" style="width:140px;">
            <option value="normal" <?= $p === 'normal' ? 'selected' : '' ?>>normal</option>
            <option value="high" <?= $p === 'high' ? 'selected' : '' ?>>high</option>
            <option value="critical" <?= $p === 'critical' ? 'selected' : '' ?>>critical</option>
          </select>
        </td>
        <td><input type="text" name="note" form="<?= $formId ?>" value="<?= htmlspecialchars((string) ($entry['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="2000"></td>
        <td>
          <label><input type="checkbox" name="is_active" form="<?= $formId ?>" value="1" <?= ((int) ($entry['is_active'] ?? 0)) === 1 ? 'checked' : '' ?> style="width:auto;"> Aktiv</label>
        </td>
        <td>
          <form id="<?= $formId ?>" method="post" action="/admin/supplier-watchlist/<?= (int) ($entry['id'] ?? 0) ?>/update">
            <button class="btn" type="submit">Spara</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Supplier watchlist | Admin';
require __DIR__ . '/../../layouts/admin.php';
