<?php
$isEdit = is_array($product);
$attributesText = '';
$primaryLink = $product['primary_supplier_link'] ?? null;
$selectedSupplierItemId = (string) ($product['supplier_item_id'] ?? $primaryLink['supplier_item_id'] ?? '');
if ($isEdit) {
    foreach (($product['attributes'] ?? []) as $attribute) {
        $attributesText .= $attribute['attribute_key'] . '|' . $attribute['attribute_value'] . PHP_EOL;
    }
}

$selectedSupplierId = (string) ($selected_supplier_id ?? '');
$stockOptions = ['in_stock', 'out_of_stock', 'backorder'];
$filterAction = $isEdit ? '/admin/products/' . (int) $product['id'] . '/edit' : '/admin/products/create';
$mediaError = (string) ($_GET['media_error'] ?? '');
ob_start();
?>
<section class="card" style="margin-bottom:.8rem;">
  <h4>Filtrera leverantörsartiklar</h4>
  <form method="get" action="<?= $filterAction ?>" style="display:grid; gap:.5rem; grid-template-columns:1fr 1fr auto; align-items:end;">
    <div>
      <label>Filter leverantör</label>
      <select name="supplier_id">
        <option value="">Alla aktiva</option>
        <?php foreach ($suppliers as $supplier): ?>
          <option value="<?= (int) $supplier['id'] ?>" <?= $selectedSupplierId === (string) $supplier['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $supplier['name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Sök leverantörsartikel (SKU/Titel)</label>
      <input name="supplier_item_query" value="<?= htmlspecialchars((string) ($supplier_item_query ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <button class="btn" type="submit">Filtrera</button>
  </form>
</section>

<section class="card">
  <h3><?= $isEdit ? 'Redigera produkt' : 'Skapa produkt' ?></h3>
  <?php if (!$isEdit && is_array($prefill_draft ?? null)): ?>
    <p class="pill warn">Manuell produktupprättning från supplier_item #<?= (int) ($prefill_draft['supplier_item']['id'] ?? 0) ?> · kontrollera publicerad data innan du sparar.</p>
    <div class="grid" style="margin-bottom:.7rem;">
      <div class="card">
        <strong>Källa: leverantörsdata</strong>
        <p class="muted" style="margin:.4rem 0 0;">Leverantör: <?= htmlspecialchars((string) ($prefill_draft['supplier_item']['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">SKU: <?= htmlspecialchars((string) ($prefill_draft['supplier_item']['supplier_sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">Titel: <?= htmlspecialchars((string) ($prefill_draft['supplier_item']['supplier_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">Pris: <?= htmlspecialchars((string) ($prefill_draft['supplier_item']['price'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">Lager: <?= htmlspecialchars((string) ($prefill_draft['supplier_item']['stock_qty'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="card">
        <strong>Databrister före skapande</strong>
        <?php foreach (($prefill_draft['source_data_gaps'] ?? []) as $gap): ?>
          <span class="pill bad"><?= htmlspecialchars((string) $gap, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endforeach; ?>
        <?php foreach (($prefill_draft['product_data_gaps'] ?? []) as $gap): ?>
          <span class="pill warn"><?= htmlspecialchars((string) $gap, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
  <form method="post" action="<?= $isEdit ? '/admin/products/' . (int) $product['id'] : '/admin/products' ?>">
    <?php if ((string) ($_GET['return_to_review'] ?? '') === '1'): ?>
      <input type="hidden" name="return_to_review" value="1">
    <?php endif; ?>
    <div class="grid">
      <div><label>Namn</label><input required name="name" value="<?= htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Slug</label><input name="slug" value="<?= htmlspecialchars((string) ($product['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>SKU</label><input name="sku" value="<?= htmlspecialchars((string) ($product['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Brand</label><select name="brand_id"><option value="">Ingen</option><?php foreach ($brands as $brand): ?><option value="<?= (int) $brand['id'] ?>" <?= (string) ($product['brand_id'] ?? '') === (string) $brand['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
      <div><label>Category</label><select name="category_id"><option value="">Ingen</option><?php foreach ($categories as $categoryOption): ?><option value="<?= (int) $categoryOption['id'] ?>" <?= (string) ($product['category_id'] ?? '') === (string) $categoryOption['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $categoryOption['name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
      <div><label><input type="checkbox" name="is_active" value="1" <?= (int) ($product['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Aktiv</label></div>
    </div>

    <h4>Publicerad pris/lager</h4>
    <div class="grid">
      <div><label>Sale price</label><input name="sale_price" value="<?= htmlspecialchars((string) ($product['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00"></div>
      <div><label>Valuta</label><input name="currency_code" value="<?= htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label>Lagerstatus</label><select name="stock_status"><?php foreach ($stockOptions as $option): ?><option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($product['stock_status'] ?? 'out_of_stock') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
      <div><label>Lagervärde (qty)</label><input name="stock_quantity" value="<?= htmlspecialchars((string) ($product['stock_quantity'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"></div>
      <div><label><input type="checkbox" name="backorder_allowed" value="1" <?= (int) ($product['backorder_allowed'] ?? 0) === 1 ? 'checked' : '' ?>> Tillåt backorder</label></div>
    </div>

    <h4>Leverantörskoppling (v1)</h4>
    <div class="grid">
      <div>
        <label>Välj supplier_item</label>
        <select name="supplier_item_id">
          <option value="">Ingen koppling</option>
          <?php foreach ($supplier_items as $item): ?>
            <option value="<?= (int) $item['id'] ?>" <?= $selectedSupplierItemId === (string) $item['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars((string) ($item['supplier_name'] ?? '-') . ' | ' . ($item['supplier_sku'] ?? '-') . ' | ' . ($item['supplier_title'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label><input type="checkbox" name="link_is_primary" value="1" <?= (isset($product['link_is_primary']) && (int) $product['link_is_primary'] === 1) || $primaryLink !== null ? 'checked' : '' ?>> Primär koppling</label>
      </div>
    </div>

    <?php if ($primaryLink !== null): ?>
      <div class="card" style="margin-top:.7rem;">
        <strong>Snapshot (primär koppling)</strong>
        <p class="muted" style="margin:.4rem 0 0;">Leverantör: <?= htmlspecialchars((string) ($primaryLink['supplier_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">SKU: <?= htmlspecialchars((string) ($primaryLink['supplier_sku_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">Titel: <?= htmlspecialchars((string) ($primaryLink['supplier_title_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">Pris: <?= htmlspecialchars((string) ($primaryLink['supplier_price_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="muted" style="margin:.2rem 0;">Lager: <?= htmlspecialchars((string) ($primaryLink['supplier_stock_snapshot'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    <?php endif; ?>

    <label>Beskrivning</label><textarea name="description"><?= htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    <label>Attribut (en rad per attribut: key|value)</label><textarea name="attributes"><?= htmlspecialchars($attributesText, ENT_QUOTES, 'UTF-8') ?></textarea>
    <br><button class="btn" type="submit">Spara</button>
  </form>
</section>



<?php if ($isEdit): ?>
<section class="card" style="margin-top:.8rem;">
  <h4>Manuell lagerjustering</h4>
  <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/operations">
    <input type="hidden" name="action" value="manual_adjust_stock">
    <div class="grid">
      <div>
        <label>Justeringssätt</label>
        <select name="stock_adjustment_mode">
          <option value="set">Sätt absolut lagersaldo</option>
          <option value="delta">Justera med plus/minus</option>
        </select>
      </div>
      <div><label>Nytt lagersaldo (set)</label><input type="number" name="stock_quantity" min="0" value="<?= (int) ($product['stock_quantity'] ?? 0) ?>"></div>
      <div><label>Delta (+/-)</label><input type="number" name="stock_delta" value="0"></div>
      <div><label>Status vid set</label><select name="stock_status"><?php foreach ($stockOptions as $option): ?><option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($product['stock_status'] ?? 'out_of_stock') === $option ? 'selected' : '' ?>><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
      <div><label><input type="checkbox" name="backorder_allowed" value="1" <?= (int) ($product['backorder_allowed'] ?? 0) === 1 ? 'checked' : '' ?>> Tillåt backorder (set)</label></div>
    </div>
    <label>Kommentar</label><input name="stock_comment" placeholder="Orsak till justering">
    <button class="btn" type="submit">Spara lagerjustering</button>
  </form>

  <h4>Senaste lagerhändelser</h4>
  <?php $movements = $stock_movements ?? []; ?>
  <?php if ($movements === []): ?>
    <p class="muted">Ingen lagerhistorik ännu.</p>
  <?php else: ?>
    <table class="table compact">
      <thead><tr><th>Typ</th><th>Delta</th><th>Från</th><th>Till</th><th>Kommentar</th><th>Skapad</th></tr></thead>
      <tbody>
      <?php foreach ($movements as $movement): ?>
        <tr>
          <td><?= htmlspecialchars((string) $movement['movement_type'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= (int) $movement['quantity_delta'] ?></td>
          <td><?= (int) $movement['previous_quantity'] ?></td>
          <td><?= (int) $movement['new_quantity'] ?></td>
          <td><?= htmlspecialchars((string) ($movement['comment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars((string) $movement['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($isEdit): ?>
<section id="media" class="card" style="margin-top:.8rem;">
  <div class="topline"><h3>Produktmedia v1</h3></div>
  <?php if ($mediaError !== ''): ?><p class="error-box"><?= htmlspecialchars($mediaError, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

  <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/images/upload" enctype="multipart/form-data" style="margin-bottom:.9rem;">
    <div class="grid" style="align-items:end;">
      <div>
        <label>Ladda upp bild(er)</label>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
      </div>
      <div>
        <label>Standard alt-text (valfritt)</label>
        <input name="default_alt_text" value="<?= htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>
    <button class="btn" type="submit">Ladda upp</button>
  </form>

  <?php $images = $product['images'] ?? []; ?>
  <?php if ($images === []): ?>
    <p class="pill bad">Produkten saknar bild.</p>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.7rem;">
      <?php foreach ($images as $image): ?>
        <article class="card">
          <img src="<?= htmlspecialchars((string) $image['image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($image['alt_text'] ?: $product['name']), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;aspect-ratio:4/3;object-fit:cover;margin-bottom:.5rem;">
          <p class="muted" style="margin:.2rem 0;"><?= (int) $image['is_primary'] === 1 ? 'Primärbild' : 'Sekundär bild' ?></p>

          <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/images/<?= (int) $image['id'] ?>/update">
            <label>Alt-text</label>
            <input name="alt_text" value="<?= htmlspecialchars((string) ($image['alt_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <label>Sort order</label>
            <input name="sort_order" type="number" value="<?= (int) ($image['sort_order'] ?? 0) ?>">
            <label><input type="checkbox" name="is_primary" value="1" <?= (int) ($image['is_primary'] ?? 0) === 1 ? 'checked' : '' ?>> Primärbild</label>
            <button class="btn" type="submit">Spara bilddata</button>
          </form>

          <?php if ((int) ($image['is_primary'] ?? 0) !== 1): ?>
            <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/images/<?= (int) $image['id'] ?>/primary" style="margin-top:.45rem;">
              <button class="btn" type="submit">Sätt som primär</button>
            </form>
          <?php endif; ?>

          <form method="post" action="/admin/products/<?= (int) $product['id'] ?>/images/<?= (int) $image['id'] ?>/delete" style="margin-top:.45rem;" onsubmit="return confirm('Ta bort bild permanent?');">
            <button class="btn" type="submit">Ta bort bild</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php else: ?>
  <section class="card" style="margin-top:.8rem;"><p class="muted">Spara produkten först för att kunna ladda upp och hantera bilder.</p></section>
<?php endif; ?>

<?php $content = (string) ob_get_clean(); $title = 'Product-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
