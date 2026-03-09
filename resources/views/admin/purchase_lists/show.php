<?php ob_start(); ?>
<section class="card">
  <div class="topline">
    <h3>Inköpsunderlag detalj</h3>
    <a class="btn" href="/admin/purchase-lists">Till lista</a>
  </div>

  <?php if ($detail === null): ?>
    <p class="error-box">Inköpsunderlaget hittades inte.</p>
  <?php else: ?>
    <?php if (($error ?? '') !== ''): ?>
      <p class="error-box"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (($message ?? '') !== ''): ?>
      <p class="pill ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns:1fr 1fr; gap:1rem;">
      <div>
        <h4><?= htmlspecialchars((string) $detail['list']['name'], ENT_QUOTES, 'UTF-8') ?></h4>
        <p style="color:#9ea0ac; margin-top:0;">Skapad: <?= htmlspecialchars((string) $detail['list']['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <form method="post" action="/admin/purchase-lists/<?= (int) $detail['list']['id'] ?>/update" class="card" style="padding:.6rem;">
        <label for="status">Status</label>
        <select id="status" name="status">
          <?php foreach (($statusOptions ?? []) as $status): ?>
            <option value="<?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?>" <?= $detail['list']['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <label for="notes">Anteckning</label>
        <textarea id="notes" name="notes"><?= htmlspecialchars((string) ($detail['list']['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        <button class="btn" type="submit" style="margin-top:.5rem;">Spara underlag</button>
      </form>
    </div>

    <table class="table compact" style="margin-top:.8rem;">
      <thead>
      <tr>
        <th>Produkt</th><th>SKU</th><th>Leverantör</th><th>Lev. SKU</th><th>Lev. pris</th><th>Lev. lager</th><th>Publicerat lager</th><th>Förslag</th><th>Vald</th><th></th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($detail['items'] as $item): ?>
        <tr>
          <td><?= htmlspecialchars((string) $item['product_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['supplier_sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) ($item['supplier_price_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= $item['supplier_stock_snapshot'] !== null ? (int) $item['supplier_stock_snapshot'] : '-' ?></td>
          <td><?= $item['current_stock_quantity'] !== null ? (int) $item['current_stock_quantity'] : '-' ?></td>
          <td><?= $item['suggested_quantity'] !== null ? (int) $item['suggested_quantity'] : '-' ?></td>
          <td>
            <form method="post" action="/admin/purchase-lists/<?= (int) $detail['list']['id'] ?>/items/<?= (int) $item['id'] ?>/quantity" class="actions-inline" style="margin:0;">
              <input type="number" min="1" name="selected_quantity" value="<?= $item['selected_quantity'] !== null ? (int) $item['selected_quantity'] : 1 ?>" style="max-width:80px;">
              <button class="btn" type="submit">Spara</button>
            </form>
          </td>
          <td><span class="pill"><?= htmlspecialchars((string) $detail['list']['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Inköpsunderlag detalj | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
