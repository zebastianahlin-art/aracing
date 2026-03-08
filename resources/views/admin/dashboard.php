<?php
ob_start();
?>
<section class="card">
  <h1>A-<span style="color:#e10600;">Racing</span> Admin</h1>
  <p>Katalogblocket är aktivt. Hantera brands, categories och products i vänstermenyn.</p>
</section>
<?php
$content = (string) ob_get_clean();
$title = 'Admin Dashboard | A-Racing';
require __DIR__ . '/../layouts/admin.php';
