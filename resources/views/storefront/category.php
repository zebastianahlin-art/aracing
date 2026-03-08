<?php
ob_start();
?>
<section class="panel">
  <h2>Kategori: <?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?></h2>
  <p>Här renderas kategoriinformation och produktlistor i nästa steg.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kategori | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
