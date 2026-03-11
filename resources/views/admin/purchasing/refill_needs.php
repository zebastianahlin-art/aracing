<?php
$filters = $filters ?? ['search' => '', 'supplier_id' => '', 'reason' => '', 'manual_status' => ''];
$reasonOptions = $reasonOptions ?? [];
$manualStatusOptions = $manualStatusOptions ?? [];
$supplierOptions = $supplierOptions ?? [];
?>
<?php ob_start(); ?>
<section class="card">
  <div class="topline">
    <h3>Restock v1 · Påfyllnadsbehov</h3>
    <a class="btn" href="/admin/purchase-order-drafts">Se inköpsutkast</a>
  </div>

  <?php if (($error ?? '') !== ''): ?>
    <p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (($message ?? '') !== ''): ?>
    <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/purchasing" class="grid" style="grid-template-columns:2fr 1fr 1fr 1fr auto; align-items:end; margin-bottom:.7rem;">
    <div>
      <label for="search">Sök produkt/SKU</label>
      <input id="search" name="search" value="<?= htmlspecialchars((string) $filters['search'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="supplier_id">Leverantör</label>
      <select id="supplier_id" name="supplier_id">
        <option value="">Alla</option>
        <?php foreach ($supplierOptions as $supplier): ?>
          <option value="<?= (int) $supplier['id'] ?>" <?= (int) ($filters['supplier_id'] ?? 0) === (int) $supplier['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="reason">Snabbfilter</label>
      <select id="reason" name="reason">
        <option value="">Alla signaler</option>
        <?php foreach ($reasonOptions as $reasonKey => $reasonLabel): ?>
          <option value="<?= htmlspecialchars((string) $reasonKey, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['reason'] ?? '') === (string) $reasonKey ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) $reasonLabel, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="manual_status">Manuell status</label>
      <select id="manual_status" name="manual_status">
        <option value="">Alla</option>
        <?php foreach ($manualStatusOptions as $statusKey => $statusLabel): ?>
          <option value="<?= htmlspecialchars((string) $statusKey, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($filters['manual_status'] ?? '') === (string) $statusKey ? 'selected' : '' ?>>
            <?= htmlspecialchars((string) $statusLabel, ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/purchasing">Nollställ</a>
    </div>
  </form>

  <form method="post" action="/admin/purchasing/purchase-order-drafts">
    <div class="actions-inline">
      <button class="btn" type="submit">Skapa inköpsutkast per leverantör</button>
    </div>

    <table class="table compact">
      <thead>
      <tr>
        <th></th><th>Produkt</th><th>Lagerstatus</th><th>Signaler</th><th>Leverantörsunderlag</th><th>Vald kvantitet</th><th>Manuell hantering</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach (($rows ?? []) as $row): ?>
        <tr>
          <td><input type="checkbox" name="selected_product_ids[]" value="<?= (int) $row['product_id'] ?>"></td>
          <td>
            <strong><?= htmlspecialchars((string) $row['product_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            SKU: <?= htmlspecialchars((string) ($row['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td>
            Status: <?= htmlspecialchars((string) ($row['stock_status'] ?? 'okänd'), ENT_QUOTES, 'UTF-8') ?><br>
            Antal: <?= $row['stock_quantity'] !== null ? (int) $row['stock_quantity'] : '-' ?><br>
            Backorder tillåten: <?= (int) ($row['backorder_allowed'] ?? 0) === 1 ? 'Ja' : 'Nej' ?>
          </td>
          <td>
            <?php foreach (($row['reason_labels'] ?? []) as $reasonLabel): ?>
              <span class="pill warn" style="display:inline-block;margin:0 0 .25rem 0;"><?= htmlspecialchars((string) $reasonLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
            <?php if ((int) ($row['active_stock_alerts'] ?? 0) > 0): ?>
              <small style="display:block;color:#9ea0ac;">Aktiva lagerbevakningar: <?= (int) $row['active_stock_alerts'] ?></small>
            <?php endif; ?>
          </td>
          <td>
            <?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
            Lev. SKU: <?= htmlspecialchars((string) ($row['supplier_sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
            Pris: <?= $row['supplier_price_snapshot'] !== null ? htmlspecialchars((string) $row['supplier_price_snapshot'], ENT_QUOTES, 'UTF-8') : '-' ?><br>
            Lev. lager (snapshot): <?= $row['supplier_stock_snapshot'] !== null ? (int) $row['supplier_stock_snapshot'] : '-' ?>
          </td>
          <td>
            <input type="number" min="1" name="selected_quantity[<?= (int) $row['product_id'] ?>]" value="<?= (int) $row['suggested_quantity'] ?>" style="max-width:90px;">
            <small style="display:block;color:#9ea0ac;">Förslag: <?= (int) $row['suggested_quantity'] ?></small>
          </td>
          <td>
            <form method="post" action="/admin/purchasing/<?= (int) $row['product_id'] ?>/flag">
              <select name="manual_status">
                <?php foreach ($manualStatusOptions as $statusKey => $statusLabel): ?>
                  <option value="<?= htmlspecialchars((string) $statusKey, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($row['manual_status'] ?? 'new') === (string) $statusKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $statusLabel, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input name="manual_note" value="<?= htmlspecialchars((string) ($row['manual_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Intern notering">
              <button class="btn" type="submit">Spara</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Restock | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
