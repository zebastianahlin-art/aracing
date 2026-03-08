<?php
ob_start();
?>
<section class="panel">
  <h2>Produkt: <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></h2>
  <p>Produktsida-skelett med plats för bilder, tekniska attribut och lagerstatus.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Produkt | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
