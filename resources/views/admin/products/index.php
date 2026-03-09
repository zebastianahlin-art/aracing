<?php ob_start(); ?>
<section class="card">
  <div class="topline"><h3>Products</h3><a class="btn" href="/admin/products/create">+ Ny produkt</a></div>
  <table class="table">
    <thead><tr><th>ID</th><th>Namn</th><th>SKU</th><th>Brand</th><th>Category</th><th>Leverantörslänk</th><th>Pris/Lager</th><th>Aktiv</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $product): ?>
      <tr>
        <td><?= (int) $product['id'] ?></td>
        <td><?= htmlspecialchars((string) $product['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) $product['sku'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($product['brand_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) ($product['category_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int) ($product['has_supplier_link'] ?? 0) === 1 ? 'Länkad' : 'Saknas' ?></td>
        <td>
          <?= $product['sale_price'] !== null ? htmlspecialchars((string) $product['sale_price'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) ($product['currency_code'] ?? 'SEK'), ENT_QUOTES, 'UTF-8') : '-' ?><br>
          <span class="muted"><?= htmlspecialchars((string) ($product['stock_status'] ?? 'okänd'), ENT_QUOTES, 'UTF-8') ?><?= $product['stock_quantity'] !== null ? ' (' . (int) $product['stock_quantity'] . ')' : '' ?></span>
        </td>
        <td><?= (int) $product['is_active'] === 1 ? 'Ja' : 'Nej' ?></td>
        <td><a class="btn" href="/admin/products/<?= (int) $product['id'] ?>/edit">Redigera</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php $content = (string) ob_get_clean(); $title = 'Products | Admin'; require __DIR__ . '/../../layouts/admin.php'; ?>
