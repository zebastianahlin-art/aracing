<?php
ob_start();
?>
<section class="panel">
  <h2>Kundvagn</h2>
  <p>Kundvagnsflöde förberett. Ingen orderlogik implementerad i denna fas.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Kundvagn | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
