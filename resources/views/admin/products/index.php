<?php
$filters = $filters ?? ['name' => '', 'sku' => '', 'active' => '', 'has_link' => '', 'deviation' => '', 'low_stock' => '', 'stock_status' => '', 'featured' => '', 'hidden' => ''];
?>
<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Operativ produktöversikt</h3><a class="btn" href="/admin/products/create">+ Ny produkt</a></div>

  <?php if (($notice ?? '') !== ''): ?>
    <p class="pill ok"><?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/admin/products" class="grid-4" style="margin-bottom:.75rem;">
    <div>
      <label for="name">Sök namn</label>
      <input id="name" name="name" value="<?= htmlspecialchars((string) $filters['name'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="sku">Sök SKU</label>
      <input id="sku" name="sku" value="<?= htmlspecialchars((string) $filters['sku'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label for="active">Aktiv</label>
      <select id="active" name="active">
        <option value="">Alla</option>
        <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Aktiv</option>
        <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Inaktiv</option>
      </select>
    </div>
    <div>
      <label for="has_link">Leverantörskoppling</label>
      <select id="has_link" name="has_link">
        <option value="">Alla</option>
        <option value="1" <?= $filters['has_link'] === '1' ? 'selected' : '' ?>>Med koppling</option>
        <option value="0" <?= $filters['has_link'] === '0' ? 'selected' : '' ?>>Utan koppling</option>
      </select>
    </div>
    <div>
      <label for="deviation">Avvikelse</label>
      <select id="deviation" name="deviation">
        <option value="">Alla</option>
        <option value="1" <?= $filters['deviation'] === '1' ? 'selected' : '' ?>>Endast avvikelse</option>
      </select>
    </div>
    <div>
      <label for="low_stock">Låg/ingen lagerstatus</label>
      <select id="low_stock" name="low_stock">
        <option value="">Alla</option>
        <option value="1" <?= $filters['low_stock'] === '1' ? 'selected' : '' ?>>Antal <= 0</option>
      </select>
    </div>
    <div>
      <label for="stock_status">Lagerstatus</label>
      <select id="stock_status" name="stock_status">
        <option value="">Alla</option>
        <?php foreach (['in_stock', 'out_of_stock', 'backorder'] as $status): ?>
          <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $filters['stock_status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="featured">Merchandising: featured</label>
      <select id="featured" name="featured">
        <option value="">Alla</option>
        <option value="1" <?= $filters['featured'] === '1' ? 'selected' : '' ?>>Featured</option>
        <option value="0" <?= $filters['featured'] === '0' ? 'selected' : '' ?>>Ej featured</option>
      </select>
    </div>
    <div>
      <label for="hidden">Publik synlighet</label>
      <select id="hidden" name="hidden">
        <option value="">Alla</option>
        <option value="0" <?= $filters['hidden'] === '0' ? 'selected' : '' ?>>Synlig</option>
        <option value="1" <?= $filters['hidden'] === '1' ? 'selected' : '' ?>>Dold i sök/listning</option>
      </select>
    </div>
    <div style="display:flex; gap:.5rem; align-items:flex-end;">
      <button class="btn" type="submit">Filtrera</button>
      <a class="btn" href="/admin/products">Nollställ</a>
    </div>
  </form>

  <form method="post" action="/admin/products/operations">
    <div class="actions-inline">
      <select name="bulk_action" style="max-width:280px;">
        <option value="">Bulkåtgärd för markerade…</option>
        <option value="sync_snapshot">Synka snapshot från supplier item</option>
        <option value="refresh_stock_status">Sätt lagerstatus från publicerat antal</option>
        <option value="set_active">Markera aktiv</option>
        <option value="set_inactive">Markera inaktiv</option>
      </select>
      <button class="btn" type="submit">Kör bulkåtgärd</button>
    </div>

    <table class="table compact">
      <thead>
      <tr>
        <th></th><th>ID</th><th>Produkt</th><th>Publicerad data</th><th>Leverantörssnapshot</th><th>Avvikelser</th><th>Snabbåtgärder</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $product): ?>
        <tr>
          <td><input type="checkbox" name="selected_product_ids[]" value="<?= (int) $product['id'] ?>"></td>
          <td><?= (int) $product['id'] ?></td>
          <td>
            <strong><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
            <span class="muted">SKU: <?= htmlspecialchars((string) ($product['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
            <span class="muted">Brand: <?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> · Kategori: <?= htmlspecialchars((string) ($product['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span><br>
            <span class="pill <?= (int) $product['is_active'] === 1 ? 'ok' : 'bad' ?>"><?= (int) $product['is_active'] === 1 ? 'Aktiv' : 'Inaktiv' ?></span>
            <span class="pill <?= (int) ($product['is_search_hidden'] ?? 0) === 1 ? 'warn' : 'ok' ?>"><?= (int) ($product['is_search_hidden'] ?? 0) === 1 ? 'Dold publikt' : 'Synlig publikt' ?></span>
            <?php if ((int) ($product['is_featured'] ?? 0) === 1): ?><span class="pill ok">Featured</span><?php endif; ?>
          </td>
          <td>
            Pris: <?= $product['sale_price'] !== null ? htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') : '-' ?><br>
            Lagerstatus: <?= htmlspecialchars((string) ($product['stock_status'] ?? 'out_of_stock'), ENT_QUOTES, 'UTF-8') ?><br>
            Antal: <?= (int) ($product['stock_quantity'] ?? 0) ?><br>
            Backorder: <?= (int) ($product['backorder_allowed'] ?? 0) === 1 ? 'Ja' : 'Nej' ?><br>
            Search boost: <?= (int) ($product['search_boost'] ?? 0) ?><br>
            Sort priority: <?= (int) ($product['sort_priority'] ?? 0) ?>
          </td>
          <td>
            Leverantör: <?= htmlspecialchars((string) ($product['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
            Lev. SKU: <?= htmlspecialchars((string) ($product['supplier_sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
            Titel: <?= htmlspecialchars((string) ($product['supplier_title_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
            Pris: <?= $product['supplier_price_snapshot'] !== null ? htmlspecialchars((string) $product['supplier_price_snapshot'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') : '-' ?><br>
            Lager: <?= $product['supplier_stock_snapshot'] !== null ? (int) $product['supplier_stock_snapshot'] : '-' ?>
          </td>
          <td>
            <?php if (($product['deviation_flags'] ?? []) === []): ?>
              <span class="pill ok">Ingen avvikelse</span>
            <?php else: ?>
              <?php foreach ($product['deviation_flags'] as $flag): ?>
                <span class="pill warn" style="margin-bottom:.2rem;"><?= htmlspecialchars((string) $flag, ENT_QUOTES, 'UTF-8') ?></span><br>
              <?php endforeach; ?>
            <?php endif; ?>
          </td>
          <td>
            <div class="actions-inline" style="margin:0;">
              <a class="btn" href="/admin/products/<?= (int) $product['id'] ?>/edit">Redigera</a>
              <button class="btn" type="submit" name="action" value="sync_snapshot" formaction="/admin/products/<?= (int) $product['id'] ?>/operations">Synka snapshot</button>
              <button class="btn" type="submit" name="action" value="copy_price" formaction="/admin/products/<?= (int) $product['id'] ?>/operations">Kopiera pris</button>
              <button class="btn" type="submit" name="action" value="copy_stock" formaction="/admin/products/<?= (int) $product['id'] ?>/operations">Kopiera lager</button>
              <button class="btn" type="submit" name="action" value="refresh_stock_status" formaction="/admin/products/<?= (int) $product['id'] ?>/operations">Sätt lagerstatus</button>
              <button class="btn" type="submit" name="action" value="<?= (int) $product['is_active'] === 1 ? 'set_inactive' : 'set_active' ?>" formaction="/admin/products/<?= (int) $product['id'] ?>/operations"><?= (int) $product['is_active'] === 1 ? 'Sätt inaktiv' : 'Sätt aktiv' ?></button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Products | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
