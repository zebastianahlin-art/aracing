<?php
ob_start();
?>
<section class="panel">
  <h2>Start</h2>
  <p>Mörkt storefront-skelett för A-Racing med fokus på katalog, tydlig lagerkommunikation och snabb serverrendering.</p>
  <ul>
    <li><a href="/category/bromsar">Exempel kategori</a></li>
    <li><a href="/product/racing-bromsbelagg-x1">Exempel produkt</a></li>
  </ul>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'A-Racing | Start';
require __DIR__ . '/../layouts/storefront.php';
