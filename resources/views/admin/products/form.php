<?php
$isEdit = is_array($product);
$attributesText = '';
$imagesText = '';
if ($isEdit) {
    foreach (($product['attributes'] ?? []) as $attribute) {
        $attributesText .= $attribute['attribute_key'] . '|' . $attribute['attribute_value'] . PHP_EOL;
    }
    foreach (($product['images'] ?? []) as $image) {
        $imagesText .= $image['image_url'] . '|' . $image['alt_text'] . '|' . $image['sort_order'] . '|' . $image['is_primary'] . PHP_EOL;
    }
}
ob_start();
?>
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
    <label>Beskrivning</label><textarea name="description"><?= htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    <label>Attribut (en rad per attribut: key|value)</label><textarea name="attributes"><?= htmlspecialchars($attributesText, ENT_QUOTES, 'UTF-8') ?></textarea>
    <label>Bilder (en rad: url|alt|sort_order|is_primary[0/1])</label><textarea name="images"><?= htmlspecialchars($imagesText, ENT_QUOTES, 'UTF-8') ?></textarea>
    <br><button class="btn" type="submit">Spara</button>
  </form>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Product-form | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
