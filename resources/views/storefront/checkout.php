<?php
ob_start();
?>
<section class="panel">
  <h2>Checkout</h2>
  <p>Checkout-skelett klart för kommande betal- och fraktintegrationer.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Checkout | A-Racing';
require __DIR__ . '/../layouts/storefront.php';
