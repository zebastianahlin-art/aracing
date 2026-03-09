<?php
$filters = $filters ?? ['search' => '', 'supplier_id' => ''];
?>
<?php ob_start(); ?>
<section class="card">
  <div class="topline">
    <h3>Inköpsöversikt · Påfyllnadsbehov</h3>
    <a class="btn" href="/admin/purchase-lists">Se inköpsunderlag</a>
  </div>

  <?php if (($error ?? '') !== ''): ?>
    <p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if (($message ?? '') !== ''): ?>
    <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/purchasing" class="grid" style="grid-template-columns:2fr 1fr auto; align-items:end; margin-bottom:.7rem;">
    <div>
      <label for="search">Sök produkt/SKU</label>
      <input id="search" name="search" value="<?= htmlspecialchars((string) $filters['search'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="supplier_id">Leverantör-ID</label>
      <input id="supplier_id" name="supplier_id" value="<?= htmlspecialchars((string) $filters['supplier_id'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/purchasing">Nollställ</a>
    </div>
  </form>

  <form method="post" action="/admin/purchasing/purchase-lists">
    <div class="grid" style="grid-template-columns:1.4fr 1fr; margin-bottom:.7rem;">
      <div>
        <label for="name">Namn på inköpsunderlag</label>
        <input id="name" name="name" required placeholder="Vecka 12 påfyllning">
      </div>
      <div>
        <label for="notes">Anteckning (valfritt)</label>
        <input id="notes" name="notes" placeholder="Manuellt underlag för lågt lagersaldo">
      </div>
    </div>

    <div class="actions-inline">
      <button class="btn" type="submit">Skapa inköpsunderlag från markerade</button>
    </div>

    <table class="table compact">
      <thead>
      <tr>
        <th></th><th>Produkt</th><th>Publicerat lager</th><th>Leverantör</th><th>Lev. data</th><th>Påfyllnad</th><th>Vald kvantitet</th>
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
            Antal: <?= $row['stock_quantity'] !== null ? (int) $row['stock_quantity'] : '-' ?>
          </td>
          <td>
            <?= htmlspecialchars((string) ($row['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
            Lev. SKU: <?= htmlspecialchars((string) ($row['supplier_sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
          </td>
          <td>
            Pris: <?= $row['supplier_price_snapshot'] !== null ? htmlspecialchars((string) $row['supplier_price_snapshot'], ENT_QUOTES, 'UTF-8') : '-' ?><br>
            Lager: <?= $row['supplier_stock_snapshot'] !== null ? (int) $row['supplier_stock_snapshot'] : '-' ?>
          </td>
          <td><span class="pill warn"><?= htmlspecialchars((string) $row['refill_indicator'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td>
            <input type="number" min="1" name="selected_quantity[<?= (int) $row['product_id'] ?>]" value="<?= (int) $row['suggested_quantity'] ?>" style="max-width:90px;">
            <small style="display:block;color:#9ea0ac;">Förslag: <?= (int) $row['suggested_quantity'] ?></small>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Inköp | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
