<?php
$isEdit = is_array($product);
$attributesText = '';
$imagesText = '';
$primaryLink = $product['primary_supplier_link'] ?? null;
$selectedSupplierItemId = (string) ($primaryLink['supplier_item_id'] ?? '');
if ($isEdit) {
    foreach (($product['attributes'] ?? []) as $attribute) {
        $attributesText .= $attribute['attribute_key'] . '|' . $attribute['attribute_value'] . PHP_EOL;
    }
    foreach (($product['images'] ?? []) as $image) {
        $imagesText .= $image['image_url'] . '|' . $image['alt_text'] . '|' . $image['sort_order'] . '|' . $image['is_primary'] . PHP_EOL;
    }
}

$selectedSupplierId = (string) ($selected_supplier_id ?? '');
$stockOptions = ['i lager', 'låg lagerstatus', 'slut i lager', 'okänd'];
$filterAction = $isEdit ? '/admin/products/' . (int) $product['id'] . '/edit' : '/admin/products/create';
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
  <form method="post" action="<?= $isEdit ? '/admin/products/' . (int) $product['id'] : '/admin/products' ?>">
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
      <div><label>Lagerstatus</label><select name="stock_status"><option value="">Välj status</option><?php foreach ($stockOptions as $option): ?><option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($product['stock_status'] ?? '') === $option ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
      <div><label>Lagervärde (qty)</label><input name="stock_quantity" value="<?= htmlspecialchars((string) ($product['stock_quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
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
        <label><input type="checkbox" name="link_is_primary" value="1" <?= $primaryLink !== null ? 'checked' : '' ?>> Primär koppling</label>
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
    <label>Bilder (en rad: url|alt|sort_order|is_primary[0/1])</label><textarea name="images"><?= htmlspecialchars($imagesText, ENT_QUOTES, 'UTF-8') ?></textarea>
    <br><button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Product-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
